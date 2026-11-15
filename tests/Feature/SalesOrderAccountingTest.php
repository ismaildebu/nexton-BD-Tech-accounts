<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\LedgerEntry;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoucherType;
use App\Services\SalesOrderAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'company_name' => 'Test Company LLC',
        'business_type' => 'Trading',
    ]);

    $this->financialYear = FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => 'FY ' . date('Y') . '-' . (date('Y') + 1),
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'is_active' => true,
        'is_closed' => false,
    ]);

    VoucherType::create([
        'company_id' => $this->company->id,
        'code' => 'SV',
        'name' => 'Sales Voucher',
        'nature' => 'journal',
        'prefix' => 'SV',
        'last_number' => 0,
        'is_active' => true,
        'status' => true,
        'description' => 'Sales order accounting entries',
    ]);

    Account::create([
        'company_id' => $this->company->id,
        'account_code' => '5001',
        'account_name' => 'Sales Revenue',
        'account_type' => 'Income',
        'nature' => 'Income',
        'balance_type' => 'Credit',
        'opening_balance' => '0.0000',
        'is_active' => true,
    ]);

    /*
     * VAT Payable (Liability) and Sales Discount (Expense).
     * Required only when a Sales Order has tax / discount.
     */
    Account::create([
        'company_id' => $this->company->id,
        'account_code' => '3005',
        'account_name' => 'VAT Payable',
        'account_type' => 'Liability',
        'nature' => 'General',
        'opening_balance' => '0.0000',
        'is_active' => true,
    ]);

    Account::create([
        'company_id' => $this->company->id,
        'account_code' => '2121',
        'account_name' => 'Sales Discount',
        'account_type' => 'Expense',
        'nature' => 'General',
        'opening_balance' => '0.0000',
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'company_id' => $this->company->id,
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '01234567890',
        'address' => '123 Test Street',
        'customer_type' => 'Individual',
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'sku' => 'TEST-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'company_id' => $this->company->id,
        'name' => 'Test Warehouse',
        'location' => 'Test Location',
    ]);

    session(['company_id' => $this->company->id]);

    ProductStock::create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);

    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);

    $this->accountingService = new SalesOrderAccountingService(
        app('App\Services\VoucherService')
    );

    /*
     * Create a Sales Order the same way SalesOrderController::store()
     * does: subtotal, tax, discount and total are all stored.
     *
     * total = subtotal + tax - discount
     *
     * $storedTotal lets a test deliberately store a wrong total.
     *
     * @param array<int, array{quantity: int|string, unit_price: int|string}> $items
     */
    $this->makeOrder = function (
        array $items,
        string $tax = '0',
        string $discount = '0',
        string $status = 'Draft',
        ?string $storedTotal = null
    ): SalesOrder {
        $subtotal = '0.0000';

        foreach ($items as $item) {
            $subtotal = bcadd(
                $subtotal,
                bcmul(
                    (string) $item['quantity'],
                    (string) $item['unit_price'],
                    4
                ),
                4
            );
        }

        $total = bcsub(
            bcadd($subtotal, $tax, 4),
            $discount,
            4
        );

        $order = SalesOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'so_number' => 'SO-' . uniqid(),
            'order_date' => now(),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $storedTotal ?? $total,
            'is_accounted' => false,
            'is_stock_deducted' => false,
        ]);

        foreach ($items as $index => $item) {
            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'product_id' => $this->product->id,
                'item_name' => 'Product ' . ($index + 1),
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => bcmul(
                    (string) $item['quantity'],
                    (string) $item['unit_price'],
                    4
                ),
            ]);
        }

        return $order;
    };

    /*
     * Find the accounting Transaction of a Sales Order.
     */
    $this->findTransaction = function (SalesOrder $order): Transaction {
        return Transaction::query()
            ->where('company_id', $this->company->id)
            ->where('reference_number', 'SO-' . $order->so_number)
            ->firstOrFail();
    };

    /*
     * Current stock quantity of the test product.
     */
    $this->stockQuantity = function (): string {
        return (string) ProductStock::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
    };
});

describe('SalesOrderAccountingService', function () {
    describe('onConfirmed', function () {
        it(
            'creates double-entry journal when SO is confirmed',
            function () {
                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 5, 'unit_price' => 100],
                    ['quantity' => 5, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = ($this->findTransaction)($salesOrder);

                $entries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', false)
                    ->get();

                expect($entries)->toHaveCount(2);

                expect(
                    bccomp(
                        (string) $entries->sum('debit_amount'),
                        (string) $entries->sum('credit_amount'),
                        4
                    )
                )->toBe(0);

                $debitEntry = $entries
                    ->where('debit_amount', '>', 0)
                    ->first();

                $creditEntry = $entries
                    ->where('credit_amount', '>', 0)
                    ->first();

                expect($debitEntry)->not->toBeNull();
                expect($creditEntry)->not->toBeNull();

                expect($debitEntry->account->nature)->toBe('Customer');
                expect($creditEntry->account->nature)->toBe('Income');

                expect((string) $debitEntry->debit_amount)->toBe('1000.0000');
                expect((string) $creditEntry->credit_amount)->toBe('1000.0000');

                expect($transaction->total_debit)->toBe('1000.0000');
                expect($transaction->total_credit)->toBe('1000.0000');

                expect($salesOrder->refresh()->is_accounted)->toBeTrue();
            }
        );

        it(
            'posts tax and discount lines and keeps debit equal to credit',
            function () {
                /*
                 * Items 1000, tax 100, discount 50  =>  total 1050
                 *
                 * Dr Accounts Receivable   1050
                 * Dr Sales Discount          50
                 *     Cr Sales Revenue            1000
                 *     Cr Sales Tax Payable         100
                 *
                 * Debit 1100 = Credit 1100
                 */
                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '100',
                    '50'
                );

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = ($this->findTransaction)($salesOrder);

                $entries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', false)
                    ->with('account')
                    ->get();

                expect($entries)->toHaveCount(4);

                expect(
                    bccomp(
                        (string) $entries->sum('debit_amount'),
                        '1100',
                        4
                    )
                )->toBe(0);

                expect(
                    bccomp(
                        (string) $entries->sum('credit_amount'),
                        '1100',
                        4
                    )
                )->toBe(0);

                $receivable = $entries->first(
                    fn ($entry) => $entry->account->nature === 'Customer'
                );

                $discount = $entries->first(
                    fn ($entry) => $entry->account->account_code === '2121'
                );

                $revenue = $entries->first(
                    fn ($entry) => $entry->account->account_code === '5001'
                );

                $tax = $entries->first(
                    fn ($entry) => $entry->account->account_code === '3005'
                );

                expect($receivable)->not->toBeNull();
                expect($discount)->not->toBeNull();
                expect($revenue)->not->toBeNull();
                expect($tax)->not->toBeNull();

                expect(bccomp((string) $receivable->debit_amount, '1050', 4))->toBe(0);
                expect(bccomp((string) $discount->debit_amount, '50', 4))->toBe(0);
                expect(bccomp((string) $revenue->credit_amount, '1000', 4))->toBe(0);
                expect(bccomp((string) $tax->credit_amount, '100', 4))->toBe(0);

                expect(
                    bccomp((string) $transaction->total_debit, '1100', 4)
                )->toBe(0);

                expect(
                    bccomp((string) $transaction->total_credit, '1100', 4)
                )->toBe(0);
            }
        );

        it(
            'rejects confirmation when stored total does not match items, tax and discount',
            function () {
                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '0',
                    '0',
                    'Draft',
                    '900.0000'
                );

                expect(
                    fn () => $this->accountingService->onConfirmed($salesOrder)
                )->toThrow(\RuntimeException::class, 'total does not match');

                // Nothing may be posted and stock must be untouched.
                expect(
                    Transaction::query()
                        ->where('company_id', $this->company->id)
                        ->where('reference_type', 'SalesOrder')
                        ->count()
                )->toBe(0);

                expect(
                    bccomp(($this->stockQuantity)(), '100', 4)
                )->toBe(0);

                expect($salesOrder->refresh()->is_accounted)->toBeFalse();
            }
        );

        it(
            'rejects confirmation when the VAT Payable account is missing',
            function () {
                Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_name', 'VAT Payable')
                    ->delete();

                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '100',
                    '0'
                );

                expect(
                    fn () => $this->accountingService->onConfirmed($salesOrder)
                )->toThrow(
                    \RuntimeException::class,
                    'VAT Payable (Liability) account is not configured'
                );

                expect(
                    Transaction::query()
                        ->where('company_id', $this->company->id)
                        ->where('reference_type', 'SalesOrder')
                        ->count()
                )->toBe(0);

                // Stock deduction must have been rolled back.
                expect(
                    bccomp(($this->stockQuantity)(), '100', 4)
                )->toBe(0);
            }
        );

        it(
            'rejects confirmation when the tax account is really a different account',
            function () {
                /*
                 * A Liability account that is not named "VAT Payable"
                 * (e.g. Loan Payable) must never receive tax postings.
                 */
                Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_name', 'VAT Payable')
                    ->update(['account_name' => 'Loan Payable']);

                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '100',
                    '0'
                );

                expect(
                    fn () => $this->accountingService->onConfirmed($salesOrder)
                )->toThrow(
                    \RuntimeException::class,
                    'VAT Payable (Liability) account is not configured'
                );

                expect(
                    Transaction::query()
                        ->where('company_id', $this->company->id)
                        ->where('reference_type', 'SalesOrder')
                        ->count()
                )->toBe(0);
            }
        );

        it(
            'assigns a short unique code to the customer receivable account',
            function () {
                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $arAccount = Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('nature', 'Customer')
                    ->firstOrFail();

                expect($arAccount->account_code)
                    ->toBe('AR-' . $this->customer->id);

                // accounts.account_code is varchar(20).
                expect(strlen($arAccount->account_code))
                    ->toBeLessThanOrEqual(20);
            }
        );

        it(
            'gives customers with the same name separate receivable accounts',
            function () {
                $otherCustomer = Customer::create([
                    'company_id' => $this->company->id,
                    'name' => $this->customer->name,
                    'email' => 'other@example.com',
                    'phone' => '01999999999',
                    'address' => '456 Test Street',
                    'customer_type' => 'Individual',
                    'is_active' => true,
                ]);

                $firstOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $secondOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 200],
                ]);

                $secondOrder->update([
                    'customer_id' => $otherCustomer->id,
                ]);

                $this->accountingService->onConfirmed($firstOrder);
                $this->accountingService->onConfirmed($secondOrder->refresh());

                $codes = Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('nature', 'Customer')
                    ->pluck('account_code')
                    ->sort()
                    ->values()
                    ->all();

                $expected = collect([
                    'AR-' . $this->customer->id,
                    'AR-' . $otherCustomer->id,
                ])->sort()->values()->all();

                expect($codes)->toBe($expected);
            }
        );

        it(
            'adopts a legacy receivable account that has no code',
            function () {
                $legacy = Account::create([
                    'company_id' => $this->company->id,
                    'account_code' => null,
                    'account_name' => "AR - {$this->customer->name}",
                    'account_type' => 'Asset',
                    'nature' => 'Customer',
                    'balance_type' => 'Debit',
                    'opening_balance' => '0.0000',
                    'is_active' => true,
                ]);

                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $accounts = Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('nature', 'Customer')
                    ->get();

                // No second account is created for the same customer.
                expect($accounts)->toHaveCount(1);
                expect($accounts->first()->id)->toBe($legacy->id);
                expect($accounts->first()->account_code)
                    ->toBe('AR-' . $this->customer->id);
            }
        );

        it(
            'finds accounts by name and type even when the chart uses different codes',
            function () {
                // Newer account templates number the chart differently.
                Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_name', 'Sales Revenue')
                    ->update(['account_code' => '4001']);

                Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_name', 'VAT Payable')
                    ->update(['account_code' => '2005']);

                Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_name', 'Sales Discount')
                    ->update(['account_code' => '5999']);

                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '100',
                    '50'
                );

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                expect($salesOrder->refresh()->is_accounted)->toBeTrue();

                $transaction = ($this->findTransaction)($salesOrder);

                expect(
                    bccomp((string) $transaction->total_debit, '1100', 4)
                )->toBe(0);

                expect(
                    bccomp((string) $transaction->total_credit, '1100', 4)
                )->toBe(0);
            }
        );

        it(
            'refuses to post when two active accounts share the same required name',
            function () {
                Account::create([
                    'company_id' => $this->company->id,
                    'account_code' => '5002',
                    'account_name' => 'Sales Revenue',
                    'account_type' => 'Income',
                    'nature' => 'Income',
                    'balance_type' => 'Credit',
                    'opening_balance' => '0.0000',
                    'is_active' => true,
                ]);

                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                expect(
                    fn () => $this->accountingService->onConfirmed($salesOrder)
                )->toThrow(
                    \RuntimeException::class,
                    'More than one active Sales Revenue (Income) account'
                );

                expect(
                    Transaction::query()
                        ->where('company_id', $this->company->id)
                        ->where('reference_type', 'SalesOrder')
                        ->count()
                )->toBe(0);
            }
        );

        it(
            'is idempotent - multiple calls do not create duplicate entries',
            function () {
                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 3, 'unit_price' => 50],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = ($this->findTransaction)($salesOrder);

                $countAfterFirst = LedgerEntry::query()
                    ->where('transaction_id', $transaction->id)
                    ->count();

                $this->accountingService->onConfirmed(
                    $salesOrder->refresh()
                );

                $countAfterSecond = LedgerEntry::query()
                    ->where('transaction_id', $transaction->id)
                    ->count();

                expect($countAfterFirst)->toBe($countAfterSecond);
                expect($countAfterSecond)->toBe(2);
            }
        );

        it(
            'throws exception if total amount is zero or negative',
            function () {
                $salesOrder = ($this->makeOrder)([]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );
            }
        )->throws(Exception::class);

        it(
            'creates or uses existing customer A/R account',
            function () {
                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $arAccount = Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('nature', 'Customer')
                    ->where('account_name', "AR - {$this->customer->name}")
                    ->first();

                expect($arAccount)->not->toBeNull();
            }
        );

        it(
            'creates or uses existing Sales Revenue account',
            function () {
                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $revenueAccount = Account::query()
                    ->where('company_id', $this->company->id)
                    ->where('account_type', 'Income')
                    ->where('nature', 'Income')
                    ->first();

                expect($revenueAccount)->not->toBeNull();
            }
        );
    });

    describe('onCancelled', function () {
        it(
            'creates reversal entries when SO is cancelled',
            function () {
                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 2, 'unit_price' => 250]],
                    '0',
                    '0',
                    'Confirmed'
                );

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $salesOrder->refresh();

                expect($salesOrder->is_accounted)->toBeTrue();

                $transaction = ($this->findTransaction)($salesOrder);

                $this->accountingService->onCancelled(
                    $salesOrder
                );

                $originalEntries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', true)
                    ->get();

                $reversalEntries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', false)
                    ->get();

                expect($originalEntries)->toHaveCount(2);
                expect($reversalEntries)->toHaveCount(2);

                expect($salesOrder->refresh()->is_accounted)->toBeFalse();

                expect($transaction->refresh()->isCancelled())->toBeTrue();
            }
        );

        it(
            'reversal entries are opposite of original entries',
            function () {
                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 1, 'unit_price' => 1000]],
                    '0',
                    '0',
                    'Confirmed'
                );

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = ($this->findTransaction)($salesOrder);

                $originalEntries = LedgerEntry::query()
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', false)
                    ->get();

                expect($originalEntries)->toHaveCount(2);

                $originalDebit = $originalEntries->sum('debit_amount');
                $originalCredit = $originalEntries->sum('credit_amount');

                $this->accountingService->onCancelled(
                    $salesOrder
                );

                $reversalEntries = LedgerEntry::query()
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', false)
                    ->get();

                $reversedOriginalEntries = LedgerEntry::query()
                    ->where('transaction_id', $transaction->id)
                    ->where('is_reversed', true)
                    ->get();

                expect($reversedOriginalEntries)->toHaveCount(2);
                expect($reversalEntries)->toHaveCount(2);

                $reversalDebit = $reversalEntries->sum('debit_amount');
                $reversalCredit = $reversalEntries->sum('credit_amount');

                expect(
                    bccomp((string) $originalDebit, (string) $reversalCredit, 4)
                )->toBe(0);

                expect(
                    bccomp((string) $originalCredit, (string) $reversalDebit, 4)
                )->toBe(0);
            }
        );

        it(
            'reverses tax and discount lines so every account nets to zero and stock is restored',
            function () {
                $salesOrder = ($this->makeOrder)(
                    [['quantity' => 2, 'unit_price' => 500]],
                    '100',
                    '50'
                );

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                expect(
                    bccomp(($this->stockQuantity)(), '98', 4)
                )->toBe(0);

                $transaction = ($this->findTransaction)($salesOrder);

                $this->accountingService->onCancelled(
                    $salesOrder->refresh()
                );

                $allEntries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->get();

                // 4 original lines + 4 reversal lines.
                expect($allEntries)->toHaveCount(8);

                foreach ($allEntries->groupBy('account_id') as $accountEntries) {
                    $net = bcsub(
                        (string) $accountEntries->sum('debit_amount'),
                        (string) $accountEntries->sum('credit_amount'),
                        4
                    );

                    expect(bccomp($net, '0', 4))->toBe(0);
                }

                expect(
                    bccomp(($this->stockQuantity)(), '100', 4)
                )->toBe(0);

                expect($salesOrder->refresh()->is_accounted)->toBeFalse();
            }
        );

        it(
            'skips if SO has no accounting entries',
            function () {
                $salesOrder = ($this->makeOrder)([]);

                $this->accountingService->onCancelled(
                    $salesOrder
                );

                expect(true)->toBeTrue();
            }
        );
    });

    describe('company scoping', function () {
        it(
            'entries belong only to specified company',
            function () {
                $otherCompany = Company::create([
                    'company_name' => 'Other Company LLC',
                    'business_type' => 'Trading',
                ]);

                $salesOrder = ($this->makeOrder)([
                    ['quantity' => 1, 'unit_price' => 100],
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = ($this->findTransaction)($salesOrder);

                $entries = LedgerEntry::query()
                    ->where('company_id', $this->company->id)
                    ->where('transaction_id', $transaction->id)
                    ->count();

                $entriesOther = LedgerEntry::query()
                    ->where('company_id', $otherCompany->id)
                    ->where('transaction_id', $transaction->id)
                    ->count();

                expect($entries)->toBe(2);
                expect($entriesOther)->toBe(0);
            }
        );
    });
});