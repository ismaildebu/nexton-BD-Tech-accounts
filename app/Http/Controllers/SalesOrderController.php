<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    public function index()
    {
        $company_id = session('company_id');
        $orders = SalesOrder::where('company_id', $company_id)
                    ->with('customer')
                    ->latest()
                    ->get();
        return view('sales-orders.index', compact('orders'));
    }

    public function create()
    {
        $company_id = session('company_id');
        $customers = Customer::where('company_id', $company_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
        return view('sales-orders.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $company_id = session('company_id');

        $validated = $request->validate([
            // IDOR fix: exists() hits the DB table directly and ignores
            // Eloquent global scopes, so it must be scoped to the current
            // company explicitly — otherwise a user could submit another
            // company's customer_id and it would pass validation.
            'customer_id'         => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $company_id),
            ],
            'order_date'          => 'required|date',
            'delivery_date'       => 'nullable|date|after_or_equal:order_date',
            'tax'                 => 'nullable|numeric|min:0',
            'discount'            => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.item_name'   => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit'        => 'nullable|string|max:50',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated, $company_id) {
            // Lock the row count read + so_number generation and the
            // order/items insert together so two concurrent requests
            // can't read the same count and produce a duplicate so_number,
            // and so a mid-loop item failure can't leave a half-built order.
            $count = SalesOrder::where('company_id', $company_id)
                        ->lockForUpdate()
                        ->count();

            $so_number = 'SO-' . date('Ymd') . '-' . str_pad(
                $count + 1, 4, '0', STR_PAD_LEFT
            );

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $tax      = $validated['tax'] ?? 0;
            $discount = $validated['discount'] ?? 0;
            $total    = $subtotal + $tax - $discount;

            $order = SalesOrder::create([
                'company_id'    => $company_id,
                'customer_id'   => $validated['customer_id'],
                'so_number'     => $so_number,
                'order_date'    => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status'        => 'Draft',
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'discount'      => $discount,
                'total'         => $total,
                'notes'         => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'item_name'      => $item['item_name'],
                    'description'    => $item['description'] ?? null,
                    'quantity'       => $item['quantity'],
                    'unit'           => $item['unit'] ?? null,
                    'unit_price'     => $item['unit_price'],
                    'total'          => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        return redirect()->route('sales-orders.show', $order)
                         ->with('success', 'Sales Order created!');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load('customer', 'items');
        return view('sales-orders.show', compact('salesOrder'));
    }

    public function updateStatus(Request $request, SalesOrder $salesOrder)
    {
        $request->validate([
            'status' => 'required|in:Draft,Confirmed,Delivered,Cancelled'
        ]);
        $salesOrder->update(['status' => $request->status]);
        return back()->with('success', 'Status updated!');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        // Business rule: once an order has moved past Draft it's been
        // acted on (confirmed with the customer, delivered, etc.) — same
        // guard as InvoiceController blocking deletion of paid invoices.
        if ($salesOrder->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be deleted. Cancel it instead.');
        }

        $salesOrder->delete();
        return redirect()->route('sales-orders.index')
                         ->with('success', 'Sales Order deleted!');
    }
}