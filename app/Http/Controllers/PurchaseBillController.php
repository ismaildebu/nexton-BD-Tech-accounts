<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseBillPayment;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseAccountingService;
use App\Support\Money;
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
                Rule::requiredIf(fn () => ! $request->filled('purchase_order_id')),
                'nullable',
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
            // At most 2 decimals: the columns store 2 places, so a 3rd place
            // used to be rounded silently and the totals no longer matched.
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'tax' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        // Every line total is rounded to 2 places first, then summed, so the
        // item totals always add up exactly to the bill subtotal.
        $lineTotals = [];
        $subtotal = '0.00';

        foreach ($request->items as $index => $item) {
            $lineTotals[$index] = Money::round(
                Money::mul($item['quantity'], $item['unit_price'])
            );

            $subtotal = Money::add($subtotal, $lineTotals[$index]);
        }

        $subtotal = Money::round($subtotal);
        $tax = Money::round($request->tax ?? 0);
        $discount = Money::round($request->discount ?? 0);
        $total = Money::round(
            Money::sub(Money::add($subtotal, $tax), $discount)
        );

        if (! Money::isPositive($total)) {
            return back()->withInput()->withErrors([
                'items' => 'Purchase Bill total must be greater than zero.',
            ]);
        }

        $bill = DB::transaction(function () use (
            $request,
            $company_id,
            $lineTotals,
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

            foreach ($request->items as $index => $item) {
                PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'account_id' => $item['account_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotals[$index],
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
                $companyId = (int) session('company_id');

                if (
                    $companyId <= 0
                    || (int) $purchaseBill->company_id !== $companyId
                ) {
                    abort(404);
                }

                $purchaseBill->load(
                    'vendor',
                    'items',
                    'payments.items.account',
                    'purchaseOrder',
                    'transaction'
                );

                $paymentAccounts = Account::query()
                    ->where('company_id', $companyId)
                    ->where('account_type', 'Asset')
                    ->where('is_active', true)
                    ->whereIn('nature', [
                        Account::NATURE_CASH,
                        Account::NATURE_BANK,
                    ])
                    ->orderBy('account_code')
                    ->get();

                return view(
                    'purchase-bills.show',
                    compact(
                        'purchaseBill',
                        'paymentAccounts'
                    )
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
                'payment_date' => [
                    'required',
                    'date',
                ],

                'payment_items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'payment_items.*.account_id' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('accounts', 'id')
                        ->where(
                            fn ($query) => $query
                                ->where('company_id', $companyId)
                                ->where('account_type', 'Asset')
                                ->where('is_active', true)
                                ->whereIn('nature', [
                                    Account::NATURE_CASH,
                                    Account::NATURE_BANK,
                                ])
                        ),
                ],

                'payment_items.*.amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'decimal:0,2',
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

                $alreadyPaid = Money::of(PurchaseBillPayment::query()
                    ->where('purchase_bill_id', $bill->id)
                    ->sum('amount'));

                $dueAmount = Money::sub($bill->total, $alreadyPaid);

                if (! Money::isPositive($dueAmount)) {
                    throw new \RuntimeException(
                        'This Purchase Bill has no outstanding due amount.'
                    );
                }

                /*
                * Calculate the payment total from the actual
                * payment lines. The client cannot submit a
                * separate total that differs from the lines.
                */
                $paymentItems = collect(
                    $request->input('payment_items', [])
                )->map(function (array $item): array {
                    return [
                        'account_id' => (int) $item['account_id'],
                        'amount' => Money::round($item['amount']),
                    ];
                });

                $amount = Money::round(
                    Money::sum($paymentItems->pluck('amount'))
                );

                if (! Money::isPositive($amount)) {
                    throw new \RuntimeException(
                        'Payment amount must be greater than zero.'
                    );
                }

                if (Money::cmp($amount, $dueAmount) > 0) {
                    throw new \RuntimeException(
                        'Payment amount cannot exceed the outstanding due amount.'
                    );
                }

                /*
                * Resolve all selected accounts again inside the
                * transaction. This protects against stale or
                * manipulated account data.
                */
                $accountIds = $paymentItems
                    ->pluck('account_id')
                    ->unique()
                    ->values();

                $accounts = Account::query()
                    ->where('company_id', $companyId)
                    ->whereIn('id', $accountIds)
                    ->where('account_type', 'Asset')
                    ->where('is_active', true)
                    ->whereIn('nature', [
                        Account::NATURE_CASH,
                        Account::NATURE_BANK,
                    ])
                    ->get()
                    ->keyBy('id');

                if ($accounts->count() !== $accountIds->count()) {
                    throw new \RuntimeException(
                        'One or more payment accounts are invalid for this company.'
                    );
                }

                /*
                * Determine the payment method from the selected
                * account nature.
                *
                * Single Cash  -> Cash
                * Single Bank  -> Bank
                * Cash + Bank   -> Mixed
                */
                $natures = $paymentItems
                    ->pluck('account_id')
                    ->map(
                        fn (int $accountId): string =>
                            $accounts->get($accountId)->nature
                    )
                    ->unique()
                    ->values();

                $paymentMethod = match (true) {
                    $natures->count() === 1
                        && $natures->first() === Account::NATURE_CASH
                        => 'Cash',

                    $natures->count() === 1
                        && $natures->first() === Account::NATURE_BANK
                        => 'Bank',

                    default => 'Mixed',
                };

                /*
                * Create one payment header.
                *
                * The header stores the total amount.
                * Individual cash/bank allocations are stored
                * in purchase_bill_payment_items.
                */
                $payment = PurchaseBillPayment::create([
                    'purchase_bill_id' => $bill->id,
                    'payment_date' => $request->payment_date,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'reference' => $request->reference,
                    'notes' => $request->notes,
                ]);

                /*
                * Create the individual payment allocation lines.
                */
                foreach ($paymentItems as $item) {
                    $payment->items()->create([
                        'account_id' => $item['account_id'],
                        'amount' => $item['amount'],
                    ]);
                }

                /*
                * Create and post one accounting voucher:
                *
                * Dr Accounts Payable       = total payment
                * Cr Cash                   = cash portion
                * Cr Bank                   = bank portion
                */
                $this->purchaseAccountingService
                    ->createPaymentVoucher($payment);

                /*
                * Recalculate bill totals from actual payment
                * records.
                */
                $totalPaid = Money::round(PurchaseBillPayment::query()
                    ->where('purchase_bill_id', $bill->id)
                    ->sum('amount'));

                $remainingDue = Money::sub($bill->total, $totalPaid);

                $status = 'Unpaid';

                if (! Money::isPositive($remainingDue)) {
                    $status = 'Paid';
                } elseif (Money::isPositive($totalPaid)) {
                    $status = 'Partial';
                }

                $bill->update([
                    'paid_amount' => $totalPaid,
                    'due_amount' => Money::isPositive($remainingDue)
                        ? Money::round($remainingDue)
                        : '0.00',
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
