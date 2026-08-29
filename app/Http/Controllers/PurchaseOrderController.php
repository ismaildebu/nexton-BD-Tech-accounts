<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    public function index()
    {
        $company_id = session('company_id');
        $orders = PurchaseOrder::where('company_id', $company_id)
                    ->with('vendor')
                    ->latest()
                    ->get();
        return view('purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        $company_id = session('company_id');
        $vendors = Vendor::where('company_id', $company_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
        return view('purchase-orders.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $company_id = session('company_id');

        $request->validate([
            // ✅ Fix: আগে শুধু 'exists:vendors,id' ছিল — company scope ছাড়া,
            // ফলে অন্য company-র vendor_id দিলেও PO তৈরি হয়ে যেত (IDOR)।
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'order_date'   => 'required|date',
            'items'        => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $company_id,
            'purchase_orders_monthly',
            PurchaseOrder::where('company_id', $company_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        );

        // Calculate totals
        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $tax      = $request->tax ?? 0;
        $discount = $request->discount ?? 0;
        $total    = $subtotal + $tax - $discount;

        // ✅ Fix: DB::transaction + lockForUpdate — আগে count()+1 দিয়ে
        // po_number জেনারেট হতো যা concurrent submit-এ duplicate PO number
        // তৈরি করতে পারত (SalesOrder-এ যেমন fix হয়েছিল, একই প্যাটার্ন)।
        $order = DB::transaction(function () use ($request, $company_id, $subtotal, $tax, $discount, $total) {
            $count = PurchaseOrder::where('company_id', $company_id)->lockForUpdate()->count();

            $po_number = 'PO-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $order = PurchaseOrder::create([
                'company_id'    => $company_id,
                'vendor_id'     => $request->vendor_id,
                'po_number'     => $po_number,
                'order_date'    => $request->order_date,
                'expected_date' => $request->expected_date,
                'status'        => 'Draft',
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'discount'      => $discount,
                'total'         => $total,
                'notes'         => $request->notes,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'item_name'         => $item['item_name'],
                    'description'       => $item['description'] ?? null,
                    'quantity'          => $item['quantity'],
                    'unit'              => $item['unit'] ?? null,
                    'unit_price'        => $item['unit_price'],
                    'total'             => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)
                         ->with('success', 'Purchase Order created!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('vendor', 'items');
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|in:Draft,Confirmed,Received,Cancelled'
        ]);
        $purchaseOrder->update(['status' => $request->status]);
        return back()->with('success', 'Status updated!');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // ✅ Fix: আগে যেকোনো status-এর PO ডিলিট করা যেত। এখন Draft ছাড়া
        // (Confirmed/Received/Cancelled) ডিলিট ব্লক করা হচ্ছে — যেন
        // downstream এ তৈরি হওয়া Purchase Bill-এর সাথে সংযোগ নষ্ট না হয়।
        if ($purchaseOrder->status !== 'Draft') {
            return back()->with('error', 'Only Draft purchase orders can be deleted.');
        }

        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')
                         ->with('success', 'Purchase Order deleted!');
    }
}