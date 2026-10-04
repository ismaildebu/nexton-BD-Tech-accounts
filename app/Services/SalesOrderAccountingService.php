<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Transaction;
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
 * Handles:
 *
 * 1. Sales Order confirmation
 *    - Validate warehouse/product stock
 *    - Deduct inventory
 *    - Create stock movement
 *    - Create accounting transaction
 *    - Post ledger
 *
 * 2. Sales Order cancellation
 *    - Reverse accounting
 *    - Restore previously deducted stock
 *
 * Confirmation is atomic:
 *
 *     Stock validation
 *          |
 *     Stock OUT
 *          |
 *     Dr Accounts Receivable   (order total = subtotal + tax - discount)
 *     Dr Sales Discount        (only when discount > 0)
 *     Cr Sales Revenue         (subtotal)
 *     Cr VAT Payable           (only when tax > 0)
 *          |
 *     Ledger posting
 *
 * Total Debit = total + discount = subtotal + tax = Total Credit.
 *
 * If any step fails, the complete transaction is rolled back.
 */
final class SalesOrderAccountingService
{
    private const SALES_VOUCHER_CODE = 'SV';

    /**
     * Accounts used for automatic Sales posting.
     *
     * Companies number their chart of accounts differently (older
     * companies use 5001 for Sales Revenue, newer templates use 4001),
     * so accounts are found by NAME and TYPE, never by code.
     *
     * These accounts must already exist for every company.
     * They are never created automatically.
     */
    private const SALES_REVENUE_NAME = 'Sales Revenue';

    private const SALES_REVENUE_TYPE = 'Income';

    private const SALES_DISCOUNT_NAME = 'Sales Discount';

    private const SALES_DISCOUNT_TYPE = 'Expense';

    private const SALES_TAX_PAYABLE_NAME = 'VAT Payable';

    private const SALES_TAX_PAYABLE_TYPE = 'Liability';

    /**
     * Maximum allowed difference between the stored Sales Order total
     * and the total recalculated from its items (rounding tolerance).
     */
    private const TOTAL_TOLERANCE = '0.0100';

    private LedgerPostingService $ledgerService;

    public function __construct(
        protected VoucherService $voucherService,
        ?LedgerPostingService $ledgerService = null,
    ) {
        $this->ledgerService = $ledgerService
            ?? app(LedgerPostingService::class);
    }

    /**
     * Confirm a Sales Order.
     *
     * The operation is idempotent.
     *
     * Confirmation requires:
     *
     * - A valid company warehouse
     * - Every Sales Order item to reference a company product
     * - Sufficient stock for every product
     * - Successful stock deduction
     * - Configured Revenue / Discount / Tax accounts (when applicable)
     * - Balanced accounting transaction
     * - Successful ledger posting
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
                "Cannot confirm Sales Order #{$salesOrder->so_number}. "
                . 'Only Draft or Confirmed orders are allowed.'
            );
        }

        if ($salesOrder->is_accounted) {
            return;
        }

        DB::transaction(function () use ($salesOrder): void {
            /*
             * Lock the Sales Order so two concurrent confirmation
             * requests cannot process it simultaneously.
             */
            $lockedSalesOrder = SalesOrder::query()
                ->where('company_id', $salesOrder->company_id)
                ->lockForUpdate()
                ->findOrFail($salesOrder->id);

            if ($lockedSalesOrder->is_accounted) {
                return;
            }

            $userId = Auth::id();

            if ($userId === null) {
                throw new RuntimeException(
                    'Authenticated user is required to create '
                    . 'Sales Order accounting entries.'
                );
            }

            /*
             * Stock must be deducted before accounting is considered
             * complete. Both operations are inside this transaction.
             */
            if (! $lockedSalesOrder->is_stock_deducted) {
                $this->deductSalesOrderStock(
                    $lockedSalesOrder
                );
            }

            $referenceNumber = $this->getReferenceNumber(
                $lockedSalesOrder
            );

            /*
             * Check for an existing transaction to maintain idempotency.
             */
            $existingTransaction = Transaction::query()
                ->where(
                    'company_id',
                    $lockedSalesOrder->company_id
                )
                ->where(
                    'reference_type',
                    'SalesOrder'
                )
                ->where(
                    'reference_id',
                    $lockedSalesOrder->id
                )
                ->where(
                    'reference_number',
                    $referenceNumber
                )
                ->lockForUpdate()
                ->first();

            if ($existingTransaction) {
                $this->handleExistingTransaction(
                    $lockedSalesOrder,
                    $existingTransaction
                );

                return;
            }

            /*
             * subtotal / tax / discount / total as bcmath strings.
             * Throws if the order is inconsistent.
             */
            $amounts = $this->calculateSalesOrderAmounts(
                $lockedSalesOrder
            );

            $companyId = (int) $lockedSalesOrder->company_id;

            $financialYearId = $this->resolveFinancialYearId(
                $lockedSalesOrder
            );

            $receivableAccount =
                $this->getOrCreateCustomerReceivableAccount(
                    (int) $lockedSalesOrder->customer_id,
                    $companyId
                );

            $revenueAccount = $this->getSalesRevenueAccount(
                $companyId
            );

            $voucherType = $this->getSalesVoucherType(
                $companyId
            );

            $soNumber = $lockedSalesOrder->so_number;

            /*
             * Dr Accounts Receivable = order total
             */
            $details = [
                [
                    'account_id' => $receivableAccount->id,
                    'description' =>
                        "Accounts Receivable - SO #{$soNumber}",
                    'debit_amount' => $amounts['total'],
                    'credit_amount' => '0.0000',
                ],
            ];

            /*
             * Dr Sales Discount = discount (only when discount exists)
             */
            if (bccomp($amounts['discount'], '0', 4) > 0) {
                $discountAccount = $this->getConfiguredAccount(
                    $companyId,
                    self::SALES_DISCOUNT_NAME,
                    self::SALES_DISCOUNT_TYPE
                );

                $details[] = [
                    'account_id' => $discountAccount->id,
                    'description' =>
                        "Sales Discount - SO #{$soNumber}",
                    'debit_amount' => $amounts['discount'],
                    'credit_amount' => '0.0000',
                ];
            }

            /*
             * Cr Sales Revenue = subtotal
             */
            $details[] = [
                'account_id' => $revenueAccount->id,
                'description' =>
                    "Sales Revenue - SO #{$soNumber}",
                'debit_amount' => '0.0000',
                'credit_amount' => $amounts['subtotal'],
            ];

            /*
             * Cr Sales Tax Payable = tax (only when tax exists)
             */
            if (bccomp($amounts['tax'], '0', 4) > 0) {
                $taxAccount = $this->getConfiguredAccount(
                    $companyId,
                    self::SALES_TAX_PAYABLE_NAME,
                    self::SALES_TAX_PAYABLE_TYPE
                );

                $details[] = [
                    'account_id' => $taxAccount->id,
                    'description' =>
                        "Sales Tax Payable - SO #{$soNumber}",
                    'debit_amount' => '0.0000',
                    'credit_amount' => $amounts['tax'],
                ];
            }

            /*
             * Defensive double-entry check before handing over to
             * VoucherService: Total Debit must equal Total Credit.
             */
            $debitSum = '0.0000';
            $creditSum = '0.0000';

            foreach ($details as $line) {
                $debitSum = bcadd(
                    $debitSum,
                    $line['debit_amount'],
                    4
                );

                $creditSum = bcadd(
                    $creditSum,
                    $line['credit_amount'],
                    4
                );
            }

            if (bccomp($debitSum, $creditSum, 4) !== 0) {
                throw new RuntimeException(
                    "Sales Order #{$soNumber} accounting is not balanced. "
                    . "Debit: {$debitSum}, Credit: {$creditSum}."
                );
            }

            $this->voucherService->createAutoPosted([
                'company_id' => $lockedSalesOrder->company_id,
                'financial_year_id' => $financialYearId,
                'voucher_type_id' => $voucherType->id,
                'voucher_date' =>
                    $lockedSalesOrder->order_date ?? Carbon::now(),

                'reference_type' => 'SalesOrder',
                'reference_id' => $lockedSalesOrder->id,
                'reference_number' => $referenceNumber,

                'narration' =>
                    "Sales Order #{$soNumber} confirmed",

                'details' => $details,
            ]);

            $lockedSalesOrder->update([
                'is_stock_deducted' => true,
                'is_accounted' => true,
            ]);
        });
    }

    /**
     * Deduct inventory for a Sales Order.
     *
     * Every ProductStock row is locked before checking quantity.
     *
     * This prevents two concurrent Sales Orders from consuming
     * the same available stock.
     *
     * @throws Throwable
     */
    private function deductSalesOrderStock(
        SalesOrder $salesOrder
    ): void {
        if (empty($salesOrder->warehouse_id)) {
            throw new RuntimeException(
                "Warehouse is required before confirming Sales Order "
                . "#{$salesOrder->so_number}."
            );
        }

        $warehouse = \App\Models\Warehouse::query()
            ->whereKey($salesOrder->warehouse_id)
            ->where('company_id', $salesOrder->company_id)
            ->first();

        if (! $warehouse) {
            throw new RuntimeException(
                "Warehouse #{$salesOrder->warehouse_id} does not belong "
                . "to company #{$salesOrder->company_id}."
            );
        }

        $items = SalesOrderItem::query()
            ->where('sales_order_id', $salesOrder->id)
            ->with([
                'product' => function ($query) use ($salesOrder): void {
                    $query->where(
                        'company_id',
                        $salesOrder->company_id
                    );
                },
            ])
            ->get();

        if ($items->isEmpty()) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} has no items."
            );
        }

        foreach ($items as $item) {
            if (empty($item->product_id)) {
                throw new RuntimeException(
                    "Sales Order #{$salesOrder->so_number} item #"
                    . "{$item->id} does not have a product selected."
                );
            }

            $product = $item->product;

            if (! $product) {
                throw new ModelNotFoundException(
                    "Product #{$item->product_id} was not found for "
                    . "company #{$salesOrder->company_id}."
                );
            }

            $quantity = (string) ($item->quantity ?? '0');

            if (bccomp($quantity, '0.0000', 4) <= 0) {
                throw new RuntimeException(
                    "Invalid quantity for product "
                    . "#{$product->id} in Sales Order "
                    . "#{$salesOrder->so_number}."
                );
            }

            /*
             * Lock the exact product/warehouse stock row.
             *
             * Do NOT firstOrCreate here. A missing row means zero stock.
             */
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $salesOrder->warehouse_id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new RuntimeException(
                    "Insufficient stock for {$product->name}. "
                    . "Available: 0.00, Required: {$quantity}."
                );
            }

            $available = (string) ($stock->quantity ?? '0');

            if (bccomp($available, $quantity, 4) < 0) {
                throw new RuntimeException(
                    "Insufficient stock for {$product->name}. "
                    . "Available: {$available}, "
                    . "Required: {$quantity}."
                );
            }

            /*
             * Product purchase price is used as the stock movement cost.
             */
            $unitCost = (string) (
                $product->purchase_price ?? '0.0000'
            );

            $totalCost = bcmul(
                $quantity,
                $unitCost,
                4
            );

            /*
             * Create the stock movement first.
             *
             * Because this is inside the DB transaction, it will be
             * rolled back if the later accounting operation fails.
             */
            StockMovement::create([
                'company_id' => $salesOrder->company_id,
                'product_id' => $product->id,
                'warehouse_id' => $salesOrder->warehouse_id,

                'type' => 'out',

                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,

                'reference' => 'SO-' . $salesOrder->so_number,
                'reference_type' => 'SalesOrder',
                'reference_id' => $salesOrder->id,

                'notes' =>
                    "Stock issued for Sales Order #"
                    . $salesOrder->so_number,

                'movement_date' => $salesOrder->order_date
                    ?? Carbon::today(),

                'created_by' => Auth::id(),
            ]);

            /*
             * The row is already locked, so this decrement cannot
             * race with another stock-out operation on this row.
             */
            $stock->decrement(
                'quantity',
                $quantity
            );
        }

        /*
         * Do not set is_stock_deducted here.
         *
         * The flag is set only after accounting and ledger posting
         * have succeeded.
         */
    }

    /**
     * Restore inventory when a confirmed Sales Order is cancelled.
     *
     * Stock is restored only when this Sales Order previously deducted it.
     *
     * @throws Throwable
     */
    private function restoreSalesOrderStock(
        SalesOrder $salesOrder
    ): void {
        if (! $salesOrder->is_stock_deducted) {
            return;
        }

        if (empty($salesOrder->warehouse_id)) {
            throw new RuntimeException(
                "Warehouse is missing for Sales Order "
                . "#{$salesOrder->so_number}."
            );
        }

        $items = SalesOrderItem::query()
            ->where('sales_order_id', $salesOrder->id)
            ->with([
                'product' => function ($query) use ($salesOrder): void {
                    $query->where(
                        'company_id',
                        $salesOrder->company_id
                    );
                },
            ])
            ->get();

        foreach ($items as $item) {
            if (empty($item->product_id)) {
                throw new RuntimeException(
                    "Cannot restore stock for Sales Order "
                    . "#{$salesOrder->so_number}: "
                    . "item #{$item->id} has no product."
                );
            }

            $product = $item->product;

            if (! $product) {
                throw new ModelNotFoundException(
                    "Product #{$item->product_id} was not found for "
                    . "company #{$salesOrder->company_id}."
                );
            }

            $quantity = (string) ($item->quantity ?? '0');

            if (bccomp($quantity, '0.0000', 4) <= 0) {
                throw new RuntimeException(
                    "Invalid quantity for product "
                    . "#{$product->id} in Sales Order "
                    . "#{$salesOrder->so_number}."
                );
            }

            /*
             * Lock the stock row before restoring it.
             */
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $salesOrder->warehouse_id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                /*
                 * A stock row should normally exist because the original
                 * confirmation deducted from it. Re-create it only as a
                 * defensive recovery path.
                 */
                $stock = ProductStock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $salesOrder->warehouse_id,
                    'quantity' => '0.0000',
                ]);

                $stock->refresh();

                /*
                 * Re-lock the newly-created row before modifying it.
                 */
                $stock = ProductStock::query()
                    ->whereKey($stock->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $unitCost = (string) (
                $product->purchase_price ?? '0.0000'
            );

            $totalCost = bcmul(
                $quantity,
                $unitCost,
                4
            );

            StockMovement::create([
                'company_id' => $salesOrder->company_id,
                'product_id' => $product->id,
                'warehouse_id' => $salesOrder->warehouse_id,

                'type' => 'in',

                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,

                'reference' => 'SO-' . $salesOrder->so_number,
                'reference_type' => 'SalesOrderCancellation',
                'reference_id' => $salesOrder->id,

                'notes' =>
                    "Stock restored from cancelled Sales Order #"
                    . $salesOrder->so_number,

                'movement_date' => Carbon::today(),

                'created_by' => Auth::id(),
            ]);

            $stock->increment(
                'quantity',
                $quantity
            );
        }
    }

    /**
     * Reverse Sales Order accounting and restore stock on cancellation.
     *
     * Both accounting reversal and stock restoration are atomic.
     * The reversal covers every ledger line of the voucher,
     * including Discount and Tax lines.
     *
     * @throws Throwable
     */
    public function onCancelled(SalesOrder $salesOrder): void
    {
        DB::transaction(function () use ($salesOrder): void {
            $lockedSalesOrder = SalesOrder::query()
                ->where('company_id', $salesOrder->company_id)
                ->lockForUpdate()
                ->findOrFail($salesOrder->id);

            if (
                ! $lockedSalesOrder->is_accounted
                && ! $lockedSalesOrder->is_stock_deducted
            ) {
                return;
            }

            /*
             * Accounting reversal.
             */
            if ($lockedSalesOrder->is_accounted) {
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
                    /*
                     * Already reversed. Continue to stock state.
                     */
                } elseif (! $transaction->isPosted()) {
                    throw new RuntimeException(
                        "Sales Order #{$lockedSalesOrder->so_number} "
                        . 'accounting transaction is not posted.'
                    );
                } else {
                    $this->ledgerService->cancel(
                        $transaction,
                        "Sales Order #{$lockedSalesOrder->so_number} cancelled"
                    );
                }
            }

            /*
             * Restore inventory only if stock was actually deducted.
             */
            if ($lockedSalesOrder->is_stock_deducted) {
                $this->restoreSalesOrderStock(
                    $lockedSalesOrder
                );
            }

            /*
             * Only clear the flags after all reversal operations
             * have completed successfully.
             */
            $lockedSalesOrder->update([
                'is_accounted' => false,
                'is_stock_deducted' => false,
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
            /*
             * Existing posted transaction means accounting is already
             * complete. Stock must also have been processed before this
             * method was reached.
             */
            $salesOrder->update([
                'is_accounted' => true,
                'is_stock_deducted' => true,
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
                'is_stock_deducted' => true,
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
                'reference_type',
                'SalesOrder'
            )
            ->where(
                'reference_id',
                $salesOrder->id
            )
            ->where(
                'reference_number',
                $this->getReferenceNumber($salesOrder)
            );

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Calculate Sales Order amounts using decimal (bcmath) arithmetic.
     *
     * subtotal = SUM(quantity * unit_price)
     * total    = subtotal + tax - discount
     *
     * The recalculated total must match the stored order total
     * (within rounding tolerance), otherwise accounting is rejected.
     *
     * @return array{subtotal: string, tax: string, discount: string, total: string}
     *
     * @throws RuntimeException
     */
    private function calculateSalesOrderAmounts(
        SalesOrder $salesOrder
    ): array {
        $subtotal = '0.0000';

        foreach (
            $salesOrder->items()->get([
                'quantity',
                'unit_price',
            ]) as $item
        ) {
            $lineTotal = bcmul(
                $this->toDecimal($item->quantity),
                $this->toDecimal($item->unit_price),
                4
            );

            $subtotal = bcadd(
                $subtotal,
                $lineTotal,
                4
            );
        }

        $tax = $this->toDecimal($salesOrder->tax);
        $discount = $this->toDecimal($salesOrder->discount);

        if (bccomp($tax, '0', 4) < 0) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} tax cannot be negative."
            );
        }

        if (bccomp($discount, '0', 4) < 0) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} discount cannot be negative."
            );
        }

        if (bccomp($subtotal, '0', 4) <= 0) {
            throw new RuntimeException(
                "Cannot create accounting entry for Sales Order "
                . "#{$salesOrder->so_number}. "
                . 'Items subtotal must be greater than zero.'
            );
        }

        $total = bcsub(
            bcadd($subtotal, $tax, 4),
            $discount,
            4
        );

        if (bccomp($total, '0', 4) <= 0) {
            throw new RuntimeException(
                "Cannot create accounting entry for Sales Order "
                . "#{$salesOrder->so_number}. "
                . 'Total amount must be greater than zero.'
            );
        }

        /*
         * The ledger must agree with the amount the customer was shown.
         */
        $difference = bcsub(
            $total,
            $this->toDecimal($salesOrder->total),
            4
        );

        if (bccomp($difference, '0', 4) < 0) {
            $difference = bcmul($difference, '-1', 4);
        }

        if (bccomp($difference, self::TOTAL_TOLERANCE, 4) > 0) {
            throw new RuntimeException(
                "Sales Order #{$salesOrder->so_number} total does not "
                . 'match its items, tax and discount. '
                . "Calculated: {$total}, Stored: "
                . $this->toDecimal($salesOrder->total) . '.'
            );
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    /**
     * Normalize a DB value into a plain decimal string for bcmath.
     */
    private function toDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        $string = (string) $value;

        if (preg_match('/^-?\d+(\.\d+)?$/', $string) === 1) {
            return bcadd($string, '0', 4);
        }

        return number_format((float) $value, 4, '.', '');
    }

    /**
     * Get or create the customer Accounts Receivable account.
     *
     * Customer and account are strictly company-scoped.
     *
     * Every customer has its own account with the unique code
     * "AR-{customer id}" (accounts.account_code is varchar(20)).
     *
     * Legacy accounts created before codes were assigned (account_code
     * is NULL) are adopted by name, but only when the customer name is
     * unambiguous, so the customer's existing ledger history stays on
     * one account.
     */
    protected function getOrCreateCustomerReceivableAccount(
        int $customerId,
        int $companyId
    ): Account {
        /*
         * Lock the customer row so two concurrent confirmations for the
         * same customer cannot create two receivable accounts.
         */
        $customer = Customer::query()
            ->whereKey($customerId)
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        if (! $customer) {
            throw new ModelNotFoundException(
                "Customer #{$customerId} not found for company "
                . "#{$companyId}."
            );
        }

        $accountCode = 'AR-' . $customer->id;

        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('account_code', $accountCode)
            ->where('account_type', 'Asset')
            ->where('nature', 'Customer')
            ->first();

        if ($account) {
            return $account;
        }

        $sameNameCustomers = Customer::query()
            ->where('company_id', $companyId)
            ->where('name', $customer->name)
            ->count();

        $accountName = $sameNameCustomers > 1
            ? "AR - {$customer->name} (#{$customer->id})"
            : "AR - {$customer->name}";

        if ($sameNameCustomers === 1) {
            $legacyAccount = Account::query()
                ->where('company_id', $companyId)
                ->where('account_name', $accountName)
                ->where('account_type', 'Asset')
                ->where('nature', 'Customer')
                ->whereNull('account_code')
                ->lockForUpdate()
                ->first();

            if ($legacyAccount) {
                $legacyAccount->update([
                    'account_code' => $accountCode,
                ]);

                return $legacyAccount;
            }
        }

        return Account::create([
            'company_id' => $companyId,
            'account_name' => $accountName,
            'account_type' => 'Asset',
            'nature' => 'Customer',
            'account_code' => $accountCode,
            'balance_type' => 'Debit',
            'opening_balance' => '0.0000',
            'is_active' => true,
        ]);
    }

    /**
     * Get the existing Sales Revenue account.
     *
     * It must never create another Sales Revenue account.
     *
     * @throws RuntimeException
     */
    protected function getSalesRevenueAccount(
        int $companyId
    ): Account {
        return $this->getConfiguredAccount(
            $companyId,
            self::SALES_REVENUE_NAME,
            self::SALES_REVENUE_TYPE
        );
    }

    /**
     * Get an existing, active, company-scoped account by NAME and TYPE.
     *
     * Used for Sales Revenue, Sales Discount and VAT Payable.
     * These accounts are never created automatically.
     *
     * The name AND type must both match, so an account that merely
     * shares the same Liability/Income type (for example "Loan Payable")
     * is never posted to by mistake. Deleted accounts are ignored.
     *
     * If more than one active account matches, posting is refused
     * instead of silently picking one.
     *
     * @throws RuntimeException
     */
    protected function getConfiguredAccount(
        int $companyId,
        string $accountName,
        string $accountType
    ): Account {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('account_name', $accountName)
            ->where('account_type', $accountType)
            ->where('is_active', true)
            ->limit(2)
            ->get();

        if ($accounts->isEmpty()) {
            throw new RuntimeException(
                "{$accountName} ({$accountType}) account is not configured "
                . "for company #{$companyId}."
            );
        }

        if ($accounts->count() > 1) {
            throw new RuntimeException(
                "More than one active {$accountName} ({$accountType}) "
                . "account exists for company #{$companyId}. "
                . 'Keep only one.'
            );
        }

        return $accounts->first();
    }

    /**
     * Get the company-specific Sales Voucher Type.
     *
     * code = SV
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
                'Sales voucher type (SV) is not configured '
                . "for company #{$companyId}."
            );
        }

        return $voucherType;
    }

    /**
     * Resolve the financial year for the Sales Order.
     *
     * @throws RuntimeException
     */
    protected function resolveFinancialYearId(
        SalesOrder $salesOrder
    ): int {
        if (! empty($salesOrder->financial_year_id)) {
            $financialYear = FinancialYear::query()
                ->whereKey($salesOrder->financial_year_id)
                ->where(
                    'company_id',
                    $salesOrder->company_id
                )
                ->first();

            if (! $financialYear) {
                throw new RuntimeException(
                    "Financial year #{$salesOrder->financial_year_id} "
                    . "does not belong to company "
                    . "#{$salesOrder->company_id}."
                );
            }

            return (int) $financialYear->id;
        }

        $date = $salesOrder->order_date
            ? Carbon::parse($salesOrder->order_date)
            : Carbon::now();

        $financialYear = FinancialYear::query()
            ->where(
                'company_id',
                $salesOrder->company_id
            )
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
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_closed',
                false
            )
            ->first();

        if (! $financialYear) {
            throw new RuntimeException(
                'Financial year could not be resolved for '
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
}