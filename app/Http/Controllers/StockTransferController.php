<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Class StockTransferController
 *
 * ✅ Fix: BelongsToCompany global scope যোগের পর controller
 * অনেক সরল হয়েছে। manual whereHas() এবং authorizeCompany()
 * সম্পূর্ণ বাদ দেওয়া হয়েছে।
 */
class StockTransferController extends Controller
{
    /**
     * সকল স্টক ট্রান্সফারের তালিকা (শুধু বর্তমান কোম্পানির)।
     *
     * ✅ আগে: whereHas('product', fn($q) => $q->where('company_id', ...))
     * ✅ এখন: BelongsToCompany global scope স্বয়ংক্রিয়ভাবে ফিল্টার করে
     */
    public function index(): View
    {
        $transfers = StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse'])
            ->latest('transfer_date')
            ->paginate(15);

        return view('stock_transfers.index', compact('transfers'));
    }

    /**
     * নতুন ট্রান্সফার তৈরির ফর্ম।
     *
     * Product ও Warehouse উভয়ে BelongsToCompany আছে —
     * global scope স্বয়ংক্রিয়ভাবে current company-র data দেবে।
     */
    public function create(): View
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stock_transfers.create', compact('products', 'warehouses'));
    }

    /**
     * নতুন ট্রান্সফার সংরক্ষণ।
     *
     * Validation-এ Rule::exists() এর সাথে company_id scope —
     * অন্য company-র product/warehouse ID দিলে validation fail করবে।
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) session('company_id');

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'from_warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],
            'to_warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
                Rule::notIn([$request->input('from_warehouse_id')]),
            ],
            'quantity'      => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
        ], [
            'to_warehouse_id.not_in' => 'Destination warehouse must be different from source warehouse.',
        ]);

        // ✅ Fix: এতদিন StockTransfer রেকর্ড তৈরি হতো কিন্তু আসল stock quantity
        // কখনো move হতো না — product_stocks টেবিলে কোনো পরিবর্তন ঘটত না,
        // কোনো audit log (StockMovement) তৈরি হতো না, আর source warehouse-এ
        // পর্যাপ্ত stock আছে কিনা সেই check-ও ছিল না। এখানে পুরো লজিকটা যোগ
        // করা হলো, ভাউচার/লেজার রিভার্সালের মতো একই transaction-safe প্যাটার্নে।
        try {
            $transfer = DB::transaction(function () use ($companyId, $validated) {
                // Source warehouse stock lock করে availability check
                $sourceStock = ProductStock::where('product_id', $validated['product_id'])
                    ->where('warehouse_id', $validated['from_warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                $available = $sourceStock->quantity ?? 0;

                if ($available < $validated['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock in source warehouse. Available: {$available}"
                    );
                }

                $transfer = StockTransfer::create([
                    'company_id'        => $companyId,
                    'product_id'        => $validated['product_id'],
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id'   => $validated['to_warehouse_id'],
                    'quantity'          => $validated['quantity'],
                    'transfer_date'     => $validated['transfer_date'],
                ]);

                // Source warehouse থেকে কমানো
                $sourceStock->decrement('quantity', $validated['quantity']);

                // Destination warehouse-এ বাড়ানো (না থাকলে তৈরি হবে)
                $destStock = ProductStock::firstOrCreate(
                    ['product_id' => $validated['product_id'], 'warehouse_id' => $validated['to_warehouse_id']],
                    ['quantity' => 0]
                );
                $destStock->increment('quantity', $validated['quantity']);

                $product = Product::find($validated['product_id']);
                $unitCost = $product->purchase_price ?? 0;

                // Audit trail — out + in, দুটোই 'transfer' reference দিয়ে
                StockMovement::create([
                    'company_id'     => $companyId,
                    'product_id'     => $validated['product_id'],
                    'warehouse_id'   => $validated['from_warehouse_id'],
                    'type'           => 'transfer',
                    'quantity'       => $validated['quantity'],
                    'unit_cost'      => $unitCost,
                    'total_cost'     => $unitCost * $validated['quantity'],
                    'reference'      => "Transfer #{$transfer->id}",
                    'reference_type' => 'transfer',
                    'reference_id'   => $transfer->id,
                    'notes'          => 'Stock transfer out',
                    'movement_date'  => $validated['transfer_date'],
                    'created_by'     => auth()->id(),
                ]);

                StockMovement::create([
                    'company_id'     => $companyId,
                    'product_id'     => $validated['product_id'],
                    'warehouse_id'   => $validated['to_warehouse_id'],
                    'type'           => 'transfer',
                    'quantity'       => $validated['quantity'],
                    'unit_cost'      => $unitCost,
                    'total_cost'     => $unitCost * $validated['quantity'],
                    'reference'      => "Transfer #{$transfer->id}",
                    'reference_type' => 'transfer',
                    'reference_id'   => $transfer->id,
                    'notes'          => 'Stock transfer in',
                    'movement_date'  => $validated['transfer_date'],
                    'created_by'     => auth()->id(),
                ]);

                return $transfer;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('stock-transfers.show', $transfer)
            ->with('success', 'Stock transfer created successfully.');
    }

    /**
     * ট্রান্সফারের বিস্তারিত।
     *
     * ✅ Route model binding + BelongsToCompany scope —
     * অন্য company-র ID দিলে automatic 404।
     * আর authorizeCompany() লাগবে না।
     */
    public function show(StockTransfer $stockTransfer): View
    {
        $stockTransfer->load(['product', 'fromWarehouse', 'toWarehouse']);

        return view('stock_transfers.show', compact('stockTransfer'));
    }

    /**
     * ট্রান্সফার রেকর্ড মুছে ফেলা।
     */
    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        // ✅ Fix: শুধু রেকর্ড ডিলিট করলে product_stocks-এর quantity আগের
        // মতোই "moved" অবস্থায় থেকে যেত (out-of-sync)। এখন destroy করলে
        // stock destination থেকে source-এ ফেরত যাবে — ঠিক ভাউচার
        // reversal-এর মতো।
        try {
            DB::transaction(function () use ($stockTransfer) {
                $destStock = ProductStock::where('product_id', $stockTransfer->product_id)
                    ->where('warehouse_id', $stockTransfer->to_warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (($destStock->quantity ?? 0) < $stockTransfer->quantity) {
                    throw new \RuntimeException(
                        'Cannot delete: destination warehouse stock has already been partially used elsewhere.'
                    );
                }

                $destStock->decrement('quantity', $stockTransfer->quantity);

                $sourceStock = ProductStock::firstOrCreate(
                    ['product_id' => $stockTransfer->product_id, 'warehouse_id' => $stockTransfer->from_warehouse_id],
                    ['quantity' => 0]
                );
                $sourceStock->increment('quantity', $stockTransfer->quantity);

                StockMovement::where('reference_type', 'transfer')
                    ->where('reference_id', $stockTransfer->id)
                    ->delete();

                $stockTransfer->delete();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('stock-transfers.index')
            ->with('success', 'Stock transfer deleted successfully.');
    }
}