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
                    "Purchase Bill #{$bill->bill_number} references a transaction "
                    . "that is not approved or posted."
                );
            }

            /*
             * Tax and discount do not currently have dedicated accounting
             * accounts in the Purchase Bill flow, so automatic posting
             * must reject non-zero values.
             */
            if ((float) $bill->tax !== 0.0) {
                throw new RuntimeException(
                    'Purchase Bill tax cannot be posted automatically until a tax account is configured.'
                );
            }

            if ((float) $bill->discount !== 0.0) {
                throw new RuntimeException(
                    'Purchase Bill discount cannot be posted automatically until a discount account is configured.'
                );
            }

            /*
             * Vendor validation.
             */
            $vendor = Vendor::query()
                ->where('company_id', $companyId)
                ->whereKey($bill->vendor_id)
                ->first();

            if (! $vendor) {
                throw new RuntimeException(
                    "Vendor for Purchase Bill #{$bill->bill_number} does not belong to the company."
                );
            }

            if (! $vendor->account_id) {
                throw new RuntimeException(
                    "Vendor #{$vendor->id} does not have a payable account configured."
                );
            }

            /*
             * Vendor account must be a company-scoped active Liability account.
             */
            $vendorAccount = Account::query()
                ->where('company_id', $companyId)
                ->whereKey($vendor->account_id)
                ->where('is_active', true)
                ->first();

            if (! $vendorAccount) {
                throw new RuntimeException(
                    "Vendor account #{$vendor->account_id} is invalid for company #{$companyId}."
                );
            }

            if ($vendorAccount->account_type !== 'Liability') {
                throw new RuntimeException(
                    "Vendor account #{$vendorAccount->id} must be a Liability account."
                );
            }

            /*
             * Determine whether this Purchase Bill is linked to a Purchase Order.
             */
            $purchaseOrder = null;

            if ($bill->purchase_order_id) {
                $purchaseOrder = PurchaseOrder::query()
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->find($bill->purchase_order_id);

                if (! $purchaseOrder) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} references a Purchase Order "
                        . "that does not belong to company #{$companyId}."
                    );
                }

                /*
                 * Only a received PO can be cleared by a Purchase Bill.
                 */
                if ($purchaseOrder->status !== 'Received') {
                    throw new RuntimeException(
                        "Purchase Order #{$purchaseOrder->id} must be Received "
                        . "before Purchase Bill #{$bill->bill_number} can be posted."
                    );
                }

                /*
                 * The PO receive must already have created a posted/approved
                 * accounting transaction.
                 *
                 * This prevents a Purchase Bill from clearing Purchase
                 * Clearing when the PO was merely marked Received without
                 * successful accounting.
                 */
                $purchaseOrderTransaction = Transaction::query()
                    ->where('company_id', $companyId)
                    ->where('reference_type', 'PurchaseOrder')
                    ->where('reference_id', $purchaseOrder->id)
                    ->whereIn(
                        'status',
                        [
                            Transaction::STATUS_APPROVED,
                            Transaction::STATUS_POSTED,
                        ]
                    )
                    ->first();

                if (! $purchaseOrderTransaction) {
                    throw new RuntimeException(
                        "Purchase Order #{$purchaseOrder->id} has no posted "
                        . "Purchase Order receive transaction."
                    );
                }

                /*
                 * The Purchase Bill vendor must match the Purchase Order vendor.
                 */
                if ((int) $purchaseOrder->vendor_id !== (int) $bill->vendor_id) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} vendor does not match "
                        . "Purchase Order #{$purchaseOrder->id}."
                    );
                }
            }

            /*
             * Purchase Bill items are always required at the document level.
             *
             * For a received PO-linked bill, item account_id is not used
             * for accounting because the inventory debit already happened
             * during PO receiving.
             */
            $items = $bill->items()->get();

            if ($items->isEmpty()) {
                throw new RuntimeException(
                    "Purchase Bill #{$bill->bill_number} has no items."
                );
            }

            $details = [];

            /*
             * -------------------------------------------------------------
             * PO-LINKED / RECEIVED PURCHASE BILL
             * -------------------------------------------------------------
             *
             * PO Receive:
             *
             * Dr Inventory
             * Cr Purchase Clearing
             *
             * Purchase Bill:
             *
             * Dr Purchase Clearing
             * Cr Vendor Payable
             */
            if ($purchaseOrder !== null) {
                $purchaseClearing = Account::query()
                    ->where('company_id', $companyId)
                    ->where('account_name', 'Purchase Clearing')
                    ->where('account_type', 'Liability')
                    ->where('is_active', true)
                    ->first();

                if (! $purchaseClearing) {
                    throw new RuntimeException(
                        "Purchase Clearing account is not configured for company #{$companyId}."
                    );
                }

                $purchaseOrderTotal = round(
                    (float) $purchaseOrder->total,
                    4
                );

                $billTotal = round(
                    (float) $bill->total,
                    4
                );

                if ($purchaseOrderTotal <= 0) {
                    throw new RuntimeException(
                        "Purchase Order #{$purchaseOrder->id} total must be greater than zero."
                    );
                }

                if ($billTotal <= 0) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} total must be greater than zero."
                    );
                }

                /*
                 * Since tax and discount are zero for automatic posting,
                 * the bill must match the received PO total exactly.
                 */
                if (abs($purchaseOrderTotal - $billTotal) > 0.0001) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} total does not match "
                        . "received Purchase Order #{$purchaseOrder->id} total."
                    );
                }

                /*
                 * Clear the temporary Purchase Clearing balance.
                 */
                $details[] = [
                    'account_id' => $purchaseClearing->id,
                    'description' =>
                        "Purchase Clearing - PO #"
                        . (
                            ! empty($purchaseOrder->po_number)
                                ? $purchaseOrder->po_number
                                : $purchaseOrder->id
                        )
                        . " - Bill #{$bill->bill_number}",
                    'debit_amount' =>
                        number_format($billTotal, 4, '.', ''),
                    'credit_amount' => '0.0000',
                ];

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
            } else {
                /*
                 * ---------------------------------------------------------
                 * STANDALONE PURCHASE BILL
                 * ---------------------------------------------------------
                 *
                 * Preserve the existing accounting behavior:
                 *
                 * Dr Purchase Expense / Asset
                 * Cr Vendor Payable
                 */
                $debitTotal = 0.0;

                foreach ($items as $item) {
                    if (! $item->account_id) {
                        throw new RuntimeException(
                            "Purchase Bill item #{$item->id} has no account configured."
                        );
                    }

                    $account = Account::query()
                        ->where('company_id', $companyId)
                        ->whereKey($item->account_id)
                        ->where('is_active', true)
                        ->first();

                    if (! $account) {
                        throw new RuntimeException(
                            "Purchase item account #{$item->account_id} is invalid "
                            . "for company #{$companyId}."
                        );
                    }

                    if (! in_array(
                        $account->account_type,
                        ['Expense', 'Asset'],
                        true
                    )) {
                        throw new RuntimeException(
                            "Purchase item account #{$account->id} "
                            . "must be an Expense or Asset account."
                        );
                    }

                    /*
                     * Cash, Bank and Customer receivable accounts are not
                     * valid destinations for a standalone purchase.
                     */
                    if (
                        $account->account_type === 'Asset'
                        && in_array(
                            $account->nature,
                            self::NON_PURCHASE_ASSET_NATURES,
                            true
                        )
                    ) {
                        throw new RuntimeException(
                            "Purchase item account #{$account->id} "
                            . "({$account->account_name}) cannot receive purchases: "
                            . "{$account->nature} accounts are not allowed."
                        );
                    }

                    $amount = round((float) $item->total, 4);

                    if ($amount <= 0) {
                        throw new RuntimeException(
                            "Purchase Bill item #{$item->id} must have a positive total."
                        );
                    }

                    $debitTotal += $amount;

                    $details[] = [
                        'account_id' => $account->id,
                        'description' =>
                            $item->item_name
                            . " - Purchase Bill #{$bill->bill_number}",
                        'debit_amount' =>
                            number_format($amount, 4, '.', ''),
                        'credit_amount' => '0.0000',
                    ];
                }

                $billTotal = round(
                    (float) $bill->total,
                    4
                );

                if ($billTotal <= 0) {
                    throw new RuntimeException(
                        "Purchase Bill #{$bill->bill_number} total must be greater than zero."
                    );
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
            }

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
     * Journal:
     * Debit  : Vendor Accounts Payable
     * Credit : Cash / Bank
     */
    public function createPaymentVoucher(
        PurchaseBillPayment $payment
    ): Transaction {
        return DB::transaction(function () use ($payment): Transaction {
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
             * Payment record must belong to this Purchase Bill.
             */
            $payment = PurchaseBillPayment::query()
                ->whereKey($payment->id)
                ->where('purchase_bill_id', $bill->id)
                ->firstOrFail();

            if ((float) $payment->amount <= 0) {
                throw new RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Duplicate protection.
             *
             * Since purchase_bill_payments does not have voucher_id,
             * the accounting transaction is linked through:
             *
             * reference_type = PurchaseBillPayment
             * reference_id   = payment.id
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
             * Resolve Cash / Bank account from payment method.
             */
            $paymentMethod = strtolower(
                trim((string) $payment->payment_method)
            );

            $accountQuery = Account::query()
                ->where('company_id', $companyId)
                ->where('account_type', 'Asset')
                ->where('is_active', true);

            if ($paymentMethod === 'cash') {
                $accountQuery->where(
                    'nature',
                    Account::NATURE_CASH
                );
            } elseif (
                in_array(
                    $paymentMethod,
                    [
                        'bank',
                        'bank transfer',
                        'bank_transfer',
                    ],
                    true
                )
            ) {
                $accountQuery->where(
                    'nature',
                    Account::NATURE_BANK
                );
            } else {
                throw new RuntimeException(
                    'Unsupported purchase payment method: '
                    . $payment->payment_method
                );
            }

            $cashBankAccount = $accountQuery
                ->orderBy('account_code')
                ->first();

            if ($cashBankAccount === null) {
                throw new RuntimeException(
                    'Cash or bank account is not configured for payment method: '
                    . $payment->payment_method
                );
            }

            /*
             * Payment date must belong to an open financial year.
             */
            $financialYear = FinancialYear::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->where('is_closed', false)
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
                    'Open financial year not found for payment date.'
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
             * createAutoPosted() performs:
             *
             * Draft -> Approved -> Ledger Posting
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
                'details' => [
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
                    [
                        'account_id' => $cashBankAccount->id,
                        'description' =>
                            'Paid by '
                            . $payment->payment_method,
                        'debit_amount' => '0.0000',
                        'credit_amount' =>
                            number_format(
                                (float) $payment->amount,
                                4,
                                '.',
                                ''
                            ),
                    ],
                ],
            ]);
        });
    }
}