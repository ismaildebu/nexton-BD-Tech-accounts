<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseBillPayment;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseBillController extends Controller
{
    public function __construct(
        private readonly PurchaseAccountingService $purchaseAccountingService,
    ) {
    }

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

        $accounts = Account::where('company_id', $company_id)
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
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'bill_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'items.*.item_name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subtotal = 0;

        foreach ($request->items as $item) {
            $subtotal +=
                (float) $item['quantity']
                * (float) $item['unit_price'];
        }

        $tax = (float) ($request->tax ?? 0);
        $discount = (float) ($request->discount ?? 0);
        $total = $subtotal + $tax - $discount;

        if ($total <= 0) {
            return back()->withInput()->withErrors([
                'items' => 'Purchase Bill total must be greater than zero.',
            ]);
        }

        $bill = DB::transaction(function () use (
            $request,
            $company_id,
            $subtotal,
            $tax,
            $discount,
            $total
        ) {
            $count = PurchaseBill::where('company_id', $company_id)
                ->lockForUpdate()
                ->count();

            $bill_number = 'BILL-' . date('Ymd') . '-' . str_pad(
                (string) ($count + 1),
                4,
                '0',
                STR_PAD_LEFT
            );

            $bill = PurchaseBill::create([
                'company_id' => $company_id,
                'vendor_id' => $request->vendor_id,
                'purchase_order_id' =>
                    $request->purchase_order_id ?? null,
                'bill_number' => $bill_number,
                'bill_date' => $request->bill_date,
                'due_date' => $request->due_date,
                'status' => 'Unpaid',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => 0,
                'due_amount' => $total,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'account_id' => $item['account_id'],
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' =>
                        (float) $item['quantity']
                        * (float) $item['unit_price'],
                ]);
            }

            $this->purchaseAccountingService
                ->createAutoVoucher($bill);

            return $bill;
        });

        return redirect()
            ->route('purchase-bills.show', $bill)
            ->with(
                'success',
                'Purchase Bill created and accounting voucher posted successfully!'
            );
    }

    public function show(PurchaseBill $purchaseBill)
    {
        $purchaseBill->load(
            'vendor',
            'items',
            'payments',
            'purchaseOrder',
            'transaction'
        );

        return view(
            'purchase-bills.show',
            compact('purchaseBill')
        );
    }

    public function addPayment(
        Request $request,
        PurchaseBill $purchaseBill
    ) {
        $companyId = (int) session('company_id');

        /*
         * Tenant protection.
         */
        if (
            $companyId <= 0
            || (int) $purchaseBill->company_id !== $companyId
        ) {
            abort(404);
        }

        $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'Cash',
                    'Bank',
                    'Bank Transfer',
                    'Bank_Transfer',
                ]),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $purchaseBill,
            $companyId
        ) {
            /*
             * Lock the bill so concurrent payments cannot
             * overpay the same Purchase Bill.
             */
            $bill = PurchaseBill::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($purchaseBill->id);

            $alreadyPaid = (float) PurchaseBillPayment::query()
                ->where('purchase_bill_id', $bill->id)
                ->sum('amount');

            $dueAmount =
                (float) $bill->total - $alreadyPaid;

            $amount = (float) $request->amount;

            if ($dueAmount <= 0.0001) {
                throw new \RuntimeException(
                    'This Purchase Bill has no outstanding due amount.'
                );
            }

            if ($amount > $dueAmount + 0.0001) {
                throw new \RuntimeException(
                    'Payment amount cannot exceed the outstanding due amount.'
                );
            }

            /*
             * Create the payment record.
             *
             * Accounting is created immediately after this inside
             * the same DB transaction.
             */
            $payment = PurchaseBillPayment::create([
                'purchase_bill_id' => $bill->id,
                'payment_date' => $request->payment_date,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference,
                'notes' => $request->notes,
            ]);

            /*
             * Create and post:
             *
             * Dr Accounts Payable
             * Cr Cash / Bank
             */
            $this->purchaseAccountingService
                ->createPaymentVoucher($payment);

            /*
             * Recalculate bill totals from actual payment records.
             */
            $totalPaid = (float) PurchaseBillPayment::query()
                ->where('purchase_bill_id', $bill->id)
                ->sum('amount');

            $remainingDue =
                (float) $bill->total - $totalPaid;

            $status = 'Unpaid';

            if ($remainingDue <= 0.0001) {
                $status = 'Paid';
            } elseif ($totalPaid > 0) {
                $status = 'Partial';
            }

            $bill->update([
                'paid_amount' => $totalPaid,
                'due_amount' => max(0, $remainingDue),
                'status' => $status,
            ]);
        });

        return back()->with(
            'success',
            'Payment added and accounting voucher posted successfully!'
        );
    }

    public function destroy(PurchaseBill $purchaseBill)
    {
        if ($purchaseBill->transaction_id !== null) {
            return back()->with(
                'error',
                'This purchase bill cannot be deleted because an accounting voucher has been posted.'
            );
        }

        if ($purchaseBill->status !== 'Unpaid') {
            return back()->with(
                'error',
                'Only unpaid bills with no payments can be deleted.'
            );
        }

        $purchaseBill->delete();

        return redirect()
            ->route('purchase-bills.index')
            ->with('success', 'Bill deleted!');
    }
}
