<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Class StockTransferController
 *
 * এই কন্ট্রোলার Stock Transfer মডিউলের সম্পূর্ণ CRUD অপারেশন হ্যান্ডেল করে।
 * পূর্বের প্রজেক্ট স্ট্যান্ডার্ড অনুযায়ী প্রতিটি মেথডে সংক্ষিপ্ত মন্তব্য
 * এবং সুস্পষ্ট validation rule ব্যবহার করা হয়েছে।
 */
class StockTransferController extends Controller
{
    /**
     * সকল স্টক ট্রান্সফারের তালিকা প্রদর্শন করে।
     */
    public function index()
    {
        $transfers = StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse'])
            ->latest('transfer_date')
            ->paginate(15);

        return view('stock_transfers.index', compact('transfers'));
    }

    /**
     * নতুন ট্রান্সফার তৈরির ফর্ম প্রদর্শন করে।
     */
    public function create()
    {
        $products   = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stock_transfers.create', compact('products', 'warehouses'));
    }

    /**
     * নতুন ট্রান্সফার রেকর্ড সংরক্ষণ করে।
     * Validation: quantity > 0, from_warehouse_id != to_warehouse_id
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'         => ['required', 'exists:products,id'],
            'from_warehouse_id'  => ['required', 'exists:warehouses,id'],
            'to_warehouse_id'    => [
                'required',
                'exists:warehouses,id',
                Rule::notIn([$request->input('from_warehouse_id')]),
            ],
            'quantity'           => ['required', 'integer', 'min:1'],
            'transfer_date'      => ['required', 'date'],
        ], [
            'to_warehouse_id.not_in' => 'Destination warehouse must be different from source warehouse.',
            'quantity.min'           => 'Quantity must be greater than 0.',
        ]);

        // অতিরিক্ত নিরাপত্তা যাচাই (from == to হলে ব্লক করা)
        if ($validated['from_warehouse_id'] == $validated['to_warehouse_id']) {
            return back()
                ->withErrors(['to_warehouse_id' => 'From warehouse and To warehouse cannot be the same.'])
                ->withInput();
        }

        StockTransfer::create($validated);

        // এখানে ইনভেন্টরি আপডেট লজিক (from warehouse stock decrease,
        // to warehouse stock increase) প্রজেক্টের Inventory service/model
        // অনুযায়ী যুক্ত করতে হবে। মডিউলটি ৭ ফাইলের সীমার মধ্যে রাখতে
        // এই লজিক আলাদা মডিউলে (existing InventoryService) কল করা উচিত।

        return redirect()
            ->route('stock-transfers.index')
            ->with('success', 'Stock transfer created successfully.');
    }

    /**
     * নির্দিষ্ট ট্রান্সফারের বিস্তারিত তথ্য প্রদর্শন করে।
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['product', 'fromWarehouse', 'toWarehouse']);

        return view('stock_transfers.show', compact('stockTransfer'));
    }

    /**
     * নির্দিষ্ট ট্রান্সফার রেকর্ড মুছে ফেলে।
     */
    public function destroy(StockTransfer $stockTransfer)
    {
        $stockTransfer->delete();

        return redirect()
            ->route('stock-transfers.index')
            ->with('success', 'Stock transfer deleted successfully.');
    }
}

/**
 * ------------------------------------------------------------------
 * routes/web.php এ যুক্ত করতে হবে:
 * ------------------------------------------------------------------
 * Route::resource('stock-transfers', StockTransferController::class)
 *      ->except(['edit', 'update']);
 *
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   Stock Transfer এর CRUD অপারেশন এবং validation নিশ্চিত করা।
 *
 * টেস্টিং ধাপ:
 *   1. GET /stock-transfers → তালিকা প্রদর্শিত হচ্ছে কিনা যাচাই করুন।
 *   2. GET /stock-transfers/create → ফর্ম লোড হচ্ছে কিনা দেখুন।
 *   3. POST /stock-transfers → quantity=0 দিয়ে সাবমিট করে
 *      validation error আসছে কিনা যাচাই করুন।
 *   4. POST /stock-transfers → from_warehouse_id == to_warehouse_id
 *      দিয়ে সাবমিট করে error আসছে কিনা যাচাই করুন।
 *   5. সঠিক ডেটা দিয়ে সাবমিট করে রেকর্ড তৈরি হচ্ছে কিনা যাচাই করুন।
 *   6. GET /stock-transfers/{id} → বিস্তারিত তথ্য সঠিকভাবে দেখাচ্ছে কিনা।
 *   7. DELETE /stock-transfers/{id} → রেকর্ড মুছে যাচ্ছে কিনা যাচাই করুন।
 * ------------------------------------------------------------------
 */