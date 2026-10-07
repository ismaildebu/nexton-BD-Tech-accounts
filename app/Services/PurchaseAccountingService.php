<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillPayment;
use App\Models\PurchaseOrder;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseAccountingService
{
    /**
     * Asset accounts that hold money or customer balances.
     *
     * Buying goods must never debit Cash, Bank or a customer's
     * Accounts Receivable.
     */
    private const NON_PURCHASE_ASSET_NATURES = [
        Account::NATURE_CASH,
        Account::NATURE_BANK,
        'Customer',
    ];

    public function __construct(
        private readonly VoucherService $voucherService,
    ) {
    }

    /**
     * Create and post the automatic Purchase Voucher for a Purchase Bill.
     *
     * Standalone Purchase Bill:
     *
     * Dr Purchase Expense / Asset
     * Cr Vendor Accounts Payable
     *
     * Received Purchase Order linked Bill:
     *
     * Dr Purchase Clearing
     * Cr Vendor Accounts Payable
     *
     * The inventory debit has already happened when the Purchase Order
     * was received, so a PO-linked bill must never debit inventory again.
     */
    public function createAutoVoucher(
        PurchaseBill $purchaseBill
    ): Transaction {
        return DB::transaction(function () use ($purchaseBill): Transaction {
            $companyId = (int) $purchaseBill->company_id;

            if ($companyId <= 0) {
                throw new RuntimeException(
                    'Purchase Bill company is invalid.'
                );
            }

            $bill = PurchaseBill::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($purchaseBill->id);

            /*
             * If the bill already points to a transaction, verify that
             * the transaction still exists and belongs to the same company.
             */
            if ($bill->transaction_id) {
                $existing = Transaction::query()
                    ->where('company_id', $companyId)
                    ->find($bill->transaction_id);

                if (! $existing) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} references a missing transaction."
                    );
                }

                /*
                 * An already posted/approved transaction is considered
                 * successfully accounted and is returned idempotently.
                 */
                if (in_array(
                    $existing->status,
                    [
                        Transaction::STATUS_APPROVED,
                        Transaction::STATUS_POSTED,
                    ],
                    true
                )) {
                    return $existing;
                }

                throw new RuntimeException(
                    "Purchase Bill #{$bill->bill_number} already has a transaction "
                    . "that is not approved or posted."
                );
            }

            /*
             * Vendor must belong to the same company.
             */
            $vendor = Vendor::query()
                ->where('company_id', $companyId)
                ->whereKey($bill->vendor_id)
                ->first();

            if ($vendor === null) {
                throw new RuntimeException(
                    "Vendor for Purchase Bill #{$bill->bill_number} "
                    . 'does not belong to the company.'
                );
            }

            if (! $vendor->account_id) {
                throw new RuntimeException(
                    "Vendor #{$vendor->id} does not have a payable account configured."
                );
            }

            /*
             * Resolve Vendor Accounts Payable account.
             */
            $vendorAccount = Account::query()
                ->where('company_id', $companyId)
                ->whereKey($vendor->account_id)
                ->where('is_active', true)
                ->first();

            if ($vendorAccount === null) {
                throw new RuntimeException(
                    "Vendor account #{$vendor->account_id} is invalid "
                    . "for company #{$companyId}."
                );
            }

            if ($vendorAccount->account_type !== 'Liability') {
                throw new RuntimeException(
                    "Vendor account #{$vendorAccount->id} "
                    . 'must be a Liability account.'
                );
            }

            /*
             * Load Purchase Order if this bill is PO-linked.
             */
            $purchaseOrder = null;

            if ($bill->purchase_order_id !== null) {
                $purchaseOrder = PurchaseOrder::query()
                    ->where('company_id', $companyId)
                    ->find($bill->purchase_order_id);

                if ($purchaseOrder === null) {
                    throw new RuntimeException(
                        "Purchase Order #{$bill->purchase_order_id} "
                        . 'does not belong to the company.'
                    );
                }
            }

            /*
             * Build purchase voucher details.
             */
            $details = [];
            $debitTotal = 0.0;
            $billTotal = (float) $bill->total;

            if ($billTotal <= 0) {
                throw new RuntimeException(
                    "Purchase Bill #{$bill->bill_number} total must be greater than zero."
                );
            }

            if ($purchaseOrder !== null) {
                /*
                 * PO-linked Purchase Bill:
                 *
                 * PO Receive:
                 *   Dr Inventory
                 *   Cr Purchase Clearing
                 *
                 * Purchase Bill:
                 *   Dr Purchase Clearing
                 *   Cr Accounts Payable
                 *
                 * This prevents inventory from being debited twice.
                 */
                $purchaseClearingAccount = Account::query()
                    ->where('company_id', $companyId)
                    ->where('account_type', 'Liability')
                    ->where('is_active', true)
                    ->where('account_name', 'Purchase Clearing')
                    ->first();

                if ($purchaseClearingAccount === null) {
                    throw new RuntimeException(
                        "Purchase Clearing account is not configured "
                        . "for company #{$companyId}."
                    );
                }

                $details[] = [
                    'account_id' => $purchaseClearingAccount->id,
                    'description' =>
                        "Purchase Clearing - Bill #{$bill->bill_number}",
                    'debit_amount' =>
                        number_format($billTotal, 4, '.', ''),
                    'credit_amount' => '0.0000',
                ];

                $debitTotal += $billTotal;
            } else {
                /*
                 * Standalone Purchase Bill:
                 *
                 * Debit the accounts configured on purchase bill items.
                 *
                 * Cash / Bank / Customer accounts are explicitly excluded
                 * from purchase debits.
                 */
                $items = $bill->items()
                    ->orderBy('id')
                    ->get();

                if ($items->isEmpty()) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} has no items."
                    );
                }

                foreach ($items as $item) {
                    $account = Account::query()
                        ->where('company_id', $companyId)
                        ->whereKey($item->account_id)
                        ->where('is_active', true)
                        ->first();

                    if ($account === null) {
                        throw new RuntimeException(
                            "Purchase Bill item account #{$item->account_id} "
                            . "is invalid for company #{$companyId}."
                        );
                    }

                    if (
                        $account->account_type === 'Asset'
                        && in_array(
                            $account->nature,
                            self::NON_PURCHASE_ASSET_NATURES,
                            true
                        )
                    ) {
                        throw new RuntimeException(
                            "Purchase Bill item account #{$account->id} "
                            . 'cannot be used as a purchase debit account.'
                        );
                    }

                    $itemTotal = (float) $item->total;

                    if ($itemTotal <= 0) {
                        throw new RuntimeException(
                            "Purchase Bill item #{$item->id} "
                            . 'must have a positive total.'
                        );
                    }

                    $details[] = [
                        'account_id' => $account->id,
                        'description' =>
                            $item->description
                                ?: $item->item_name,
                        'debit_amount' =>
                            number_format($itemTotal, 4, '.', ''),
                        'credit_amount' => '0.0000',
                    ];

                    $debitTotal += $itemTotal;
                }

                /*
                 * Item debits must exactly match the Purchase Bill total.
                 */
                if (abs($debitTotal - $billTotal) > 0.0001) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} is not balanced "
                        . "for automatic accounting."
                    );
                }
            }

            /*
             * Credit Vendor Accounts Payable.
             */
            $details[] = [
                'account_id' => $vendorAccount->id,
                'description' =>
                    "Accounts Payable - {$vendor->name} "
                    . "- Bill #{$bill->bill_number}",
                'debit_amount' => '0.0000',
                'credit_amount' =>
                    number_format($billTotal, 4, '.', ''),
            ];

            /*
             * Purchase Voucher type.
             */
            $voucherType = VoucherType::query()
                ->where('company_id', $companyId)
                ->where('code', 'PUV')
                ->where('is_active', true)
                ->first();

            if (! $voucherType) {
                throw new RuntimeException(
                    "Purchase Voucher type (PUV) is not configured for company #{$companyId}."
                );
            }

            /*
             * Financial year must contain the bill date.
             */
            $financialYear = FinancialYear::query()
                ->where('company_id', $companyId)
                ->whereDate('start_date', '<=', $bill->bill_date)
                ->whereDate('end_date', '>=', $bill->bill_date)
                ->first();

            if (! $financialYear) {
                throw new RuntimeException(
                    "No financial year found for Purchase Bill #{$bill->bill_number}."
                );
            }

            $referenceNumber = 'PB-' . $bill->bill_number;

            /*
             * Reference-level idempotency protection.
             *
             * This protects against a transaction existing while
             * purchase_bill.transaction_id was not yet linked.
             */
            $existing = Transaction::query()
                ->where('company_id', $companyId)
                ->where('reference_type', 'PurchaseBill')
                ->where('reference_id', $bill->id)
                ->first();

            if ($existing) {
                if (! in_array(
                    $existing->status,
                    [
                        Transaction::STATUS_APPROVED,
                        Transaction::STATUS_POSTED,
                    ],
                    true
                )) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} already has a transaction "
                        . "that is not approved or posted."
                    );
                }

                $bill->update([
                    'transaction_id' => $existing->id,
                ]);

                return $existing;
            }

            /*
             * createAutoPosted() performs:
             *
             * Draft -> Approved -> Ledger Posting
             *
             * Do not call LedgerPostingService directly here.
             */
            $transaction = $this->voucherService->createAutoPosted([
                'company_id' => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_date' => $bill->bill_date,
                'reference_type' => 'PurchaseBill',
                'reference_id' => $bill->id,
                'reference_number' => $referenceNumber,
                'narration' =>
                    $purchaseOrder !== null
                        ? "Purchase Bill #{$bill->bill_number} "
                            . "- PO #"
                            . (
                                ! empty($purchaseOrder->po_number)
                                    ? $purchaseOrder->po_number
                                    : $purchaseOrder->id
                            )
                            . " - {$vendor->name}"
                        : "Purchase Bill #{$bill->bill_number} - {$vendor->name}",
                'details' => $details,
            ]);

            /*
             * Link the source bill to its accounting transaction.
             */
            $bill->update([
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    /**
     * Create and post accounting voucher for a Purchase Bill payment.
     *
     * Single payment:
     *
     * Dr Vendor Accounts Payable
     * Cr Cash / Bank
     *
     * Split payment:
     *
     * Dr Vendor Accounts Payable
     * Cr Cash
     * Cr Bank
     *
     * The payment header stores the total amount and
     * purchase_bill_payment_items stores the individual
     * Cash / Bank allocations.
     */
    public function createPaymentVoucher(
        PurchaseBillPayment $payment
    ): Transaction {
        return DB::transaction(function () use ($payment): Transaction {
            /*
             * Lock the Purchase Bill so concurrent payments cannot
             * overpay the same bill.
             */
            $bill = PurchaseBill::query()
                ->whereKey($payment->purchase_bill_id)
                ->lockForUpdate()
                ->firstOrFail();

            $companyId = (int) $bill->company_id;

            if ($companyId <= 0) {
                throw new RuntimeException(
                    'Purchase Bill company is invalid.'
                );
            }

            /*
             * Payment must belong to this Purchase Bill.
             *
             * Load payment allocation items together with their accounts.
             */
            $payment = PurchaseBillPayment::query()
                ->whereKey($payment->id)
                ->where('purchase_bill_id', $bill->id)
                ->with('items.account')
                ->firstOrFail();

            if ((float) $payment->amount <= 0) {
                throw new RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Duplicate protection.
             *
             * One PurchaseBillPayment can create only one
             * accounting transaction.
             */
            $existing = Transaction::query()
                ->where('company_id', $companyId)
                ->where('reference_type', 'PurchaseBillPayment')
                ->where('reference_id', $payment->id)
                ->first();

            if ($existing !== null) {
                if (in_array(
                    $existing->status,
                    [
                        Transaction::STATUS_APPROVED,
                        Transaction::STATUS_POSTED,
                    ],
                    true
                )) {
                    return $existing;
                }

                throw new RuntimeException(
                    "Purchase Bill Payment #{$payment->id} references "
                    . "a transaction that is not approved or posted."
                );
            }

            /*
             * Vendor must belong to the same company.
             */
            $vendor = Vendor::query()
                ->where('company_id', $companyId)
                ->whereKey($bill->vendor_id)
                ->first();

            if ($vendor === null) {
                throw new RuntimeException(
                    "Vendor for Purchase Bill #{$bill->bill_number} "
                    . 'does not belong to the company.'
                );
            }

            if (! $vendor->account_id) {
                throw new RuntimeException(
                    "Vendor #{$vendor->id} does not have a payable account configured."
                );
            }

            /*
             * Resolve Vendor Accounts Payable account.
             */
            $payableAccount = Account::query()
                ->where('company_id', $companyId)
                ->whereKey($vendor->account_id)
                ->where('is_active', true)
                ->first();

            if ($payableAccount === null) {
                throw new RuntimeException(
                    "Vendor account #{$vendor->account_id} is invalid "
                    . "for company #{$companyId}."
                );
            }

            if ($payableAccount->account_type !== 'Liability') {
                throw new RuntimeException(
                    "Vendor account #{$payableAccount->id} "
                    . 'must be a Liability account.'
                );
            }

            /*
             * Calculate the amount already paid before this payment.
             */
            $previousPaid = (float) PurchaseBillPayment::query()
                ->where('purchase_bill_id', $bill->id)
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            $remainingDue =
                (float) $bill->total - $previousPaid;

            if ($remainingDue <= 0.0001) {
                throw new RuntimeException(
                    "Purchase Bill #{$bill->bill_number} "
                    . 'has no outstanding due amount.'
                );
            }

            if (
                (float) $payment->amount
                > $remainingDue + 0.0001
            ) {
                throw new RuntimeException(
                    'Payment amount exceeds the outstanding '
                    . 'Purchase Bill due amount.'
                );
            }

            /*
             * Payment allocation items are the source of truth
             * for Cash / Bank accounts.
             *
             * Example:
             *
             * Cash = 200
             * Bank = 300
             *
             * Payment total = 500
             */
            $paymentItems = $payment->items;

            if ($paymentItems->isEmpty()) {
                throw new RuntimeException(
                    "Purchase Bill Payment #{$payment->id} "
                    . 'has no payment allocation items.'
                );
            }

            /*
             * Validate every payment account against the current
             * company and account state.
             */
            foreach ($paymentItems as $item) {
                $account = $item->account;

                if ($account === null) {
                    throw new RuntimeException(
                        "Payment account #{$item->account_id} does not exist."
                    );
                }

                if ((int) $account->company_id !== $companyId) {
                    throw new RuntimeException(
                        "Payment account #{$account->id} does not belong "
                        . "to company #{$companyId}."
                    );
                }

                if ($account->account_type !== 'Asset') {
                    throw new RuntimeException(
                        "Payment account #{$account->id} "
                        . 'must be an Asset account.'
                    );
                }

                if (! $account->is_active) {
                    throw new RuntimeException(
                        "Payment account #{$account->id} is inactive."
                    );
                }

                if (! in_array(
                    $account->nature,
                    [
                        Account::NATURE_CASH,
                        Account::NATURE_BANK,
                    ],
                    true
                )) {
                    throw new RuntimeException(
                        "Payment account #{$account->id} must be "
                        . 'a Cash or Bank account.'
                    );
                }

                if ((float) $item->amount <= 0) {
                    throw new RuntimeException(
                        "Payment allocation for account #{$account->id} "
                        . 'must be greater than zero.'
                    );
                }
            }

            /*
             * The sum of all allocation lines must exactly equal
             * the payment header amount.
             */
            $allocationTotal = (float) $paymentItems->sum(
                fn ($item): float => (float) $item->amount
            );

            if (
                abs($allocationTotal - (float) $payment->amount)
                > 0.0001
            ) {
                throw new RuntimeException(
                    "Purchase Bill Payment #{$payment->id} allocation total "
                    . 'does not match the payment amount.'
                );
            }

            /*
             * Build voucher details.
             *
             * Debit:
             *   Vendor Accounts Payable = total payment
             *
             * Credit:
             *   Each selected Cash / Bank account = its allocation
             */
            $details = [
                [
                    'account_id' => $payableAccount->id,
                    'description' =>
                        "Payment against Purchase Bill #"
                        . $bill->bill_number,
                    'debit_amount' =>
                        number_format(
                            (float) $payment->amount,
                            4,
                            '.',
                            ''
                        ),
                    'credit_amount' => '0.0000',
                ],
            ];

            foreach ($paymentItems as $item) {
                $details[] = [
                    'account_id' => $item->account_id,
                    'description' =>
                        'Paid from '
                        . $item->account->account_name,
                    'debit_amount' => '0.0000',
                    'credit_amount' =>
                        number_format(
                            (float) $item->amount,
                            4,
                            '.',
                            ''
                        ),
                ];
            }

            /*
             * Explicit double-entry balance verification.
             *
             * This is intentionally checked before posting.
             */
            $debitTotal = 0.0;
            $creditTotal = 0.0;

            foreach ($details as $detail) {
                $debitTotal += (float) $detail['debit_amount'];
                $creditTotal += (float) $detail['credit_amount'];
            }

            if (abs($debitTotal - $creditTotal) > 0.0001) {
                throw new RuntimeException(
                    "Purchase Bill Payment #{$payment->id} "
                    . 'is not balanced.'
                );
            }

            /*
             * Existing Payment Voucher type.
             */
            $voucherType = VoucherType::query()
                ->where('company_id', $companyId)
                ->where('code', 'PV')
                ->where('is_active', true)
                ->where('status', 1)
                ->first();

            if ($voucherType === null) {
                throw new RuntimeException(
                    "Payment Voucher type (PV) is not configured "
                    . "for company #{$companyId}."
                );
            }

            /*
             * Financial year must contain the payment date.
             */
            $financialYear = FinancialYear::query()
                ->where('company_id', $companyId)
                ->whereDate(
                    'start_date',
                    '<=',
                    $payment->payment_date
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $payment->payment_date
                )
                ->first();

            if ($financialYear === null) {
                throw new RuntimeException(
                    "No financial year found for Purchase Bill Payment #"
                    . $payment->id . '.'
                );
            }

            /*
             * createAutoPosted() performs:
             *
             * Draft -> Approved -> Ledger Posting
             *
             * Do not call LedgerPostingService directly here.
             */
            return $this->voucherService->createAutoPosted([
                'company_id' => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_date' => $payment->payment_date,
                'reference_type' => 'PurchaseBillPayment',
                'reference_id' => $payment->id,
                'reference_number' =>
                    'PBP-' . $bill->bill_number . '-' . $payment->id,
                'narration' =>
                    "Payment against Purchase Bill #{$bill->bill_number}"
                    . " - {$vendor->name}",
                'details' => $details,
            ]);
        });
    }
}