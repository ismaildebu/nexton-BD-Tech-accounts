<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VoucherValidationException;
use App\Models\Account;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\SalesOrder;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * SalesOrderAccountingService
 *
 * Handles accounting transactions generated from Sales Orders.
 *
 * Accounting entry on confirmation:
 *
 *     Debit  - Customer Accounts Receivable
 *     Credit - Sales Revenue
 *
 * Accounting reversal on cancellation:
 *
 *     Debit/Credit are reversed by LedgerPostingService.
 *
 * The service is company-scoped and uses the existing accounting
 * architecture:
 *
 * SalesOrder
 *     -> Transaction
 *     -> TransactionDetail
 *     -> LedgerPostingService
 *     -> LedgerEntry
 */
final class SalesOrderAccountingService
{
    private const SALES_VOUCHER_CODE = 'SV';

    public function __construct(
        protected VoucherService $voucherService,
        ?LedgerPostingService $ledgerService = null,
    ) {
        $this->ledgerService = $ledgerService
            ?? app(LedgerPostingService::class);
    }

    private LedgerPostingService $ledgerService;

    /**
     * Create and post accounting transaction when a Sales Order is confirmed.
     *
     * Accounting:
     *
     *     Dr Accounts Receivable
     *     Cr Sales Revenue
     *
     * The operation is idempotent. Calling this method repeatedly for the
     * same Sales Order must never create duplicate active ledger entries.
     *
     * @throws Throwable
     */
    public function onConfirmed(SalesOrder $salesOrder): void
    {
        if (
            $salesOrder->status !== 'Draft'
            && $salesOrder->status !== 'Confirmed'
        ) {
            throw new RuntimeException(
                "Cannot create accounting entry for SO #{$salesOrder->so_number}. "
                . 'Only Draft or Confirmed orders are allowed.'
            );
        }

        if ($salesOrder->is_accounted) {
            return;
        }

        DB::transaction(function () use ($salesOrder): void {
            $lockedSalesOrder = SalesOrder::query()
                ->where('company_id', $salesOrder->company_id)
                ->lockForUpdate()
                ->findOrFail($salesOrder->id);

            if ($lockedSalesOrder->is_accounted) {
                return;
            }

            $referenceNumber = $this->getReferenceNumber(
                $lockedSalesOrder
            );

            /*
             * Find an existing accounting transaction belonging to the
             * same company and Sales Order.
             *
             * Sales vouchers are identified by code SV because the current
             * voucher_types schema does not contain a slug column.
             */
            $existingTransaction = Transaction::query()
                ->where('company_id', $lockedSalesOrder->company_id)
                ->where(
                    'reference_number',
                    $referenceNumber
                )
                ->whereHas('voucherType', function ($query): void {
                    $query->where(
                        'company_id',
                        DB::raw('transactions.company_id')
                    );
                    $query->where(
                        'code',
                        self::SALES_VOUCHER_CODE
                    );
                })
                ->lockForUpdate()
                ->first();

            if ($existingTransaction) {
                $this->handleExistingTransaction(
                    $lockedSalesOrder,
                    $existingTransaction
                );

                return;
            }

            $totalAmount = $this->calculateSalesOrderTotal(
                $lockedSalesOrder
            );

            if (bccomp($totalAmount, '0.0000', 4) <= 0) {
                throw new RuntimeException(
                    "Cannot create accounting entry for SO #{$lockedSalesOrder->so_number}. "
                    . 'Total amount must be greater than zero.'
                );
            }

            $financialYearId = $this->resolveFinancialYearId(
                $lockedSalesOrder
            );

            $receivableAccount =
                $this->getOrCreateCustomerReceivableAccount(
                    (int) $lockedSalesOrder->customer_id,
                    (int) $lockedSalesOrder->company_id
                );

            $revenueAccount = $this->getSalesRevenueAccount(
                (int) $lockedSalesOrder->company_id
            );

            $voucherType = $this->getSalesVoucherType(
                (int) $lockedSalesOrder->company_id
            );

            $now = Carbon::now();
            $userId = Auth::id();

            if ($userId === null) {
                throw new RuntimeException(
                    'Authenticated user is required to create Sales Order accounting entries.'
                );
            }

          $transaction = Transaction::create([
    'company_id'        => $lockedSalesOrder->company_id,
    'financial_year_id' => $financialYearId,
    'voucher_type_id'   => $voucherType->id,
    'created_by'        => $userId,

    'account_id'        => $receivableAccount->id,
    'transaction_type'  => 'Sales',
    'amount'            => $totalAmount,
    'transaction_date'  => $lockedSalesOrder->order_date ?? $now,
    'voucher_no'        => 'SO-' . $lockedSalesOrder->so_number,
    'voucher_number'    => 'SO-' . $lockedSalesOrder->so_number,
    'voucher_date'      => $lockedSalesOrder->order_date ?? $now,
    'reference_number'  => $referenceNumber,
    'narration'         => "Sales Order #{$lockedSalesOrder->so_number} confirmed",
    'total_debit'       => $totalAmount,
    'total_credit'      => $totalAmount,
    'status'            => Transaction::STATUS_APPROVED,
    'approved_by'       => $userId,
    'approved_at'       => $now,
    'approval_note'     => 'Automatically approved for Sales Order accounting.',
]);
            

            TransactionDetail::insert([
                [
                    'transaction_id' => $transaction->id,
                    'account_id'     => $receivableAccount->id,
                    'sort_order'     => 1,
                    'description'    =>
                        "Accounts Receivable - SO #{$lockedSalesOrder->so_number}",
                    'debit_amount'   => $totalAmount,
                    'credit_amount'  => '0.0000',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ],
                [
                    'transaction_id' => $transaction->id,
                    'account_id'     => $revenueAccount->id,
                    'sort_order'     => 2,
                    'description'    =>
                        "Sales Revenue - SO #{$lockedSalesOrder->so_number}",
                    'debit_amount'   => '0.0000',
                    'credit_amount'  => $totalAmount,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ],
            ]);

            $transaction->load('details');

            if (! $transaction->isBalanced) {
                throw new RuntimeException(
                    "Sales Order #{$lockedSalesOrder->so_number} "
                    . 'accounting transaction is not balanced.'
                );
            }

            /*
             * Only approved transactions may be posted.
             */
            $this->ledgerService->post($transaction);

            $lockedSalesOrder->update([
                'is_accounted' => true,
            ]);
        });
    }

    /**
     * Reverse Sales Order accounting when the Sales Order is cancelled.
     *
     * @throws Throwable
     */
    public function onCancelled(SalesOrder $salesOrder): void
    {
        if (! $salesOrder->is_accounted) {
            return;
        }

        DB::transaction(function () use ($salesOrder): void {
            $lockedSalesOrder = SalesOrder::query()
                ->where('company_id', $salesOrder->company_id)
                ->lockForUpdate()
                ->findOrFail($salesOrder->id);

            if (! $lockedSalesOrder->is_accounted) {
                return;
            }

            $transaction = $this->findSalesTransaction(
                $lockedSalesOrder,
                lock: true
            );

            if (! $transaction) {
                throw new ModelNotFoundException(
                    "Accounting transaction for Sales Order "
                    . "#{$lockedSalesOrder->so_number} was not found."
                );
            }

            if ($transaction->isCancelled()) {
                $lockedSalesOrder->update([
                    'is_accounted' => false,
                ]);

                return;
            }

            if (! $transaction->isPosted()) {
                throw new RuntimeException(
                    "Sales Order #{$lockedSalesOrder->so_number} "
                    . 'accounting transaction is not posted.'
                );
            }

            $this->ledgerService->cancel(
                $transaction,
                "Sales Order #{$lockedSalesOrder->so_number} cancelled"
            );

            $lockedSalesOrder->update([
                'is_accounted' => false,
            ]);
        });
    }

    /**
     * Handle an existing Sales Order accounting transaction.
     *
     * This method guarantees idempotent behaviour.
     *
     * @throws Throwable
     */
    private function handleExistingTransaction(
        SalesOrder $salesOrder,
        Transaction $transaction
    ): void {
        if ($transaction->isPosted()) {
            $salesOrder->update([
                'is_accounted' => true,
            ]);

            return;
        }

        if ($transaction->isCancelled()) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} already has "
                . 'a cancelled accounting transaction.'
            );
        }

        if ($transaction->isApproved()) {
            $this->ledgerService->post($transaction);

            $salesOrder->update([
                'is_accounted' => true,
            ]);

            return;
        }

        if (
            $transaction->isDraft()
            || $transaction->isSubmitted()
        ) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} has an existing "
                . 'accounting transaction that is not approved.'
            );
        }

        throw new RuntimeException(
            "Sales Order #{$salesOrder->so_number} has an invalid "
            . 'accounting transaction state.'
        );
    }

    /**
     * Find the Sales Order accounting transaction.
     */
    private function findSalesTransaction(
        SalesOrder $salesOrder,
        bool $lock = false
    ): ?Transaction {
        $query = Transaction::query()
            ->where(
                'company_id',
                $salesOrder->company_id
            )
            ->where(
                'reference_number',
                $this->getReferenceNumber($salesOrder)
            )
            ->whereHas('voucherType', function ($query): void {
                $query
                    ->where(
                        'company_id',
                        DB::raw('transactions.company_id')
                    )
                    ->where(
                        'code',
                        self::SALES_VOUCHER_CODE
                    );
            });

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Calculate the Sales Order total using decimal arithmetic.
     */
    private function calculateSalesOrderTotal(
        SalesOrder $salesOrder
    ): string {
        $total = '0.0000';

        foreach ($salesOrder->items()->get([
            'quantity',
            'unit_price',
        ]) as $item) {
            $quantity = (string) ($item->quantity ?? '0');
            $unitPrice = (string) ($item->unit_price ?? '0');

            $lineTotal = bcmul(
                $quantity,
                $unitPrice,
                4
            );

            $total = bcadd(
                $total,
                $lineTotal,
                4
            );
        }

        return $total;
    }

    /**
     * Get or create the customer Accounts Receivable account.
     *
     * The customer and account are strictly company-scoped.
     */
    protected function getOrCreateCustomerReceivableAccount(
        int $customerId,
        int $companyId
    ): Account {
        $customer = Customer::query()
            ->whereKey($customerId)
            ->where('company_id', $companyId)
            ->first();

        if (! $customer) {
            throw new ModelNotFoundException(
                "Customer #{$customerId} not found for company #{$companyId}."
            );
        }

        $accountName = "AR - {$customer->name}";

        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('account_name', $accountName)
            ->where('account_type', 'Asset')
            ->where('nature', 'Customer')
            ->first();

        if ($account) {
            return $account;
        }

        return Account::create([
            'company_id'      => $companyId,
            'account_name'    => $accountName,
            'account_type'    => 'Asset',
            'nature'          => 'Customer',
            'code'            => $this->generateAccountCode(
                $companyId,
                'AR'
            ),
            'opening_balance' => '0.0000',
        ]);
    }

    /**
     * Get or create the Sales Revenue account.
     */
    protected function getSalesRevenueAccount(
        int $companyId
    ): Account {
        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('account_type', 'Income')
            ->where('nature', 'Sales')
            ->first();

        if ($account) {
            return $account;
        }

        return Account::create([
            'company_id'      => $companyId,
            'account_name'    => 'Sales Revenue',
            'account_type'    => 'Income',
            'nature'          => 'Sales',
            'code'            => $this->generateAccountCode(
                $companyId,
                'SALES'
            ),
            'opening_balance' => '0.0000',
        ]);
    }

    /**
     * Get the company-specific Sales Voucher Type.
     *
     * The current voucher_types schema identifies voucher types using
     * code, not slug.
     *
     * Sales Voucher:
     *
     *     code   = SV
     *     nature = journal
     *
     * @throws RuntimeException
     */
    protected function getSalesVoucherType(
        int $companyId
    ): VoucherType {
        $voucherType = VoucherType::query()
            ->where('company_id', $companyId)
            ->where('code', self::SALES_VOUCHER_CODE)
            ->where('is_active', true)
            ->where('status', true)
            ->first();

        if (! $voucherType) {
            throw new RuntimeException(
                "Sales voucher type (SV) is not configured "
                . "for company #{$companyId}."
            );
        }

        return $voucherType;
    }

    /**
     * Resolve the financial year for the Sales Order.
     *
     * Existing financial_year_id is accepted only when it belongs
     * to the same company.
     *
     * @throws RuntimeException
     */
    protected function resolveFinancialYearId(
        SalesOrder $salesOrder
    ): int {
        if (! empty($salesOrder->financial_year_id)) {
            $financialYear = FinancialYear::query()
                ->whereKey($salesOrder->financial_year_id)
                ->where('company_id', $salesOrder->company_id)
                ->first();

            if (! $financialYear) {
                throw new RuntimeException(
                    "Financial year #{$salesOrder->financial_year_id} "
                    . "does not belong to company #{$salesOrder->company_id}."
                );
            }

            return (int) $financialYear->id;
        }

        $date = $salesOrder->order_date
            ? Carbon::parse($salesOrder->order_date)
            : Carbon::now();

        $financialYear = FinancialYear::query()
            ->where('company_id', $salesOrder->company_id)
            ->whereDate(
                'start_date',
                '<=',
                $date->toDateString()
            )
            ->whereDate(
                'end_date',
                '>=',
                $date->toDateString()
            )
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();

        if (! $financialYear) {
            throw new RuntimeException(
                "Financial year could not be resolved for "
                . "Sales Order #{$salesOrder->so_number}."
            );
        }

        return (int) $financialYear->id;
    }

    /**
     * Get unique Sales Order accounting reference.
     */
    protected function getReferenceNumber(
        SalesOrder $salesOrder
    ): string {
        return 'SO-' . $salesOrder->so_number;
    }

    /**
     * Generate a unique account code.
     *
     * The generated code is checked against the same company before use.
     */
    protected function generateAccountCode(
        int $companyId,
        string $prefix
    ): string {
        do {
            $code = sprintf(
                '%s-%d-%s',
                $prefix,
                $companyId,
                now()->format('YmdHisv')
            );

            $exists = Account::query()
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->exists();

            if ($exists) {
                usleep(1000);
            }
        } while ($exists);

        return $code;
    }
}