<?php

namespace App\Http\Controllers;

use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseBillPayment;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseBillController extends Controller
{
    public function index()
    {
        $company_id = session('company_id');
        $bills = PurchaseBill::where('company_id', $company_id)
                    ->with('vendor')
                    ->latest()
                    ->get();
        return view('purchase-bills.index', compact('bills'));
    }

    public function create(Request $request)
{
    $company_id = session('company_id');

    $vendors = Vendor::where('company_id', $company_id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    $accounts = \App\Models\Account::where('company_id', $company_id)
        ->where('is_active', true)
        ->whereIn('account_type', ['Expense', 'Asset'])
        ->orderBy('account_code')
        ->get();

    $purchase_order = null;

    if ($request->po_id) {
        $purchase_order = PurchaseOrder::with('items', 'vendor')
            ->where('company_id', $company_id)
            ->findOrFail($request->po_id);
    }

    $purchase_orders = PurchaseOrder::where('company_id', $company_id)
        ->whereIn('status', ['Confirmed', 'Received'])
        ->with('vendor')
        ->get();

    return view(
        'purchase-bills.create',
        compact(
            'vendors',
            'accounts',
            'purchase_order',
            'purchase_orders'
        )
    );
}

    public function store(Request $request)
    {
        $company_id = session('company_id');

        $request->validate([
            // ✅ Fix: আগে শুধু 'exists:vendors,id' ছিল — company scope ছাড়া,
            // ফলে অন্য company-র vendor_id দিয়েও Bill তৈরি করা যেত (IDOR)।
            'vendor_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('vendors', 'id')
                    ->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'bill_date'    => 'required|date',
            'items'        => 'required|array|min:1',
           'items.*.account_id' => [
    'required',
    'integer',
    \Illuminate\Validation\Rule::exists('accounts', 'id')
        ->where(fn ($query) => $query->where('company_id', $company_id)),
],
'items.*.item_name'  => 'required|string',
'items.*.quantity'   => 'required|numeric|min:0.01',
'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Calculate totals
        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $tax      = $request->tax ?? 0;
        $discount = $request->discount ?? 0;
        $total    = $subtotal + $tax - $discount;

        // ✅ Fix: DB::transaction + lockForUpdate যেন concurrent submit-এ
        // bill_number duplicate না হয়, আর bill+items atomically তৈরি হয়।
        $bill = DB::transaction(function () use ($request, $company_id, $subtotal, $tax, $discount, $total) {
            $count = PurchaseBill::where('company_id', $company_id)->lockForUpdate()->count();

            $bill_number = 'BILL-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $bill = PurchaseBill::create([
                'company_id'        => $company_id,
                'vendor_id'         => $request->vendor_id,
                'purchase_order_id' => $request->purchase_order_id ?? null,
                'bill_number'       => $bill_number,
                'bill_date'         => $request->bill_date,
                'due_date'          => $request->due_date,
                'status'            => 'Unpaid',
                'subtotal'          => $subtotal,
                'tax'               => $tax,
                'discount'          => $discount,
                'total'             => $total,
                'paid_amount'       => 0,
                'due_amount'        => $total,
                'notes'             => $request->notes,
            ]);

            foreach ($request->items as $item) {
                PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'account_id'       => $item['account_id'],
                    'item_name'        => $item['item_name'],
                    'description'      => $item['description'] ?? null,
                    'quantity'         => $item['quantity'],
                    'unit'             => $item['unit'] ?? null,
                    'unit_price'       => $item['unit_price'],
                    'total'            => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $bill;
        });

        return redirect()->route('purchase-bills.show', $bill)
                         ->with('success', 'Purchase Bill created!');
    }

    public function show(PurchaseBill $purchaseBill)
    {
        $purchaseBill->load('vendor', 'items', 'payments', 'purchaseOrder');
        return view('purchase-bills.show', compact('purchaseBill'));
    }

    public function addPayment(Request $request, PurchaseBill $purchaseBill)
    {
        $request->validate([
            'payment_date'   => 'required|date',
            'amount'         => 'required|numeric|min:0.01|max:' . $purchaseBill->due_amount,
            'payment_method' => 'required|string',
        ]);

        PurchaseBillPayment::create([
            'purchase_bill_id' => $purchaseBill->id,
            'payment_date'     => $request->payment_date,
            'amount'           => $request->amount,
            'payment_method'   => $request->payment_method,
            'reference'        => $request->reference,
            'notes'            => $request->notes,
        ]);

        $paid_amount = $purchaseBill->payments()->sum('amount');
        $due_amount  = $purchaseBill->total - $paid_amount;

        $status = 'Unpaid';
        if ($due_amount <= 0) {
            $status = 'Paid';
        } elseif ($paid_amount > 0) {
            $status = 'Partial';
        }

        $purchaseBill->update([
            'paid_amount' => $paid_amount,
            'due_amount'  => max(0, $due_amount),
            'status'      => $status,
        ]);

        return back()->with('success', 'Payment added!');
    }

    public function destroy(PurchaseBill $purchaseBill)
    {
        // ✅ Fix: আগে payment থাকা সত্ত্বেও Bill ডিলিট করা যেত, ফলে
        // paid_amount/due_amount হিসাব রেখেই payment history হারিয়ে যেত।
        // Invoice-এর paid-invoice guard-এর সাথে সামঞ্জস্যপূর্ণ ফিক্স।
        if ($purchaseBill->status !== 'Unpaid') {
            return back()->with('error', 'Only unpaid bills (with no payments) can be deleted.');
        }

        $purchaseBill->delete();
        return redirect()->route('purchase-bills.index')
                         ->with('success', 'Bill deleted!');
    }
}