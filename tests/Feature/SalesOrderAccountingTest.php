<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
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

    $this->customer = Customer::create([
        'company_id' => $this->company->id,
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '01234567890',
        'address' => '123 Test Street',
        'customer_type' => 'Individual',
        'is_active' => true,
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
});

describe('SalesOrderAccountingService', function () {
    describe('onConfirmed', function () {
        it(
            'creates double-entry journal when SO is confirmed',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 5,
                    'unit_price' => 100,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 2',
                    'quantity' => 5,
                    'unit_price' => 100,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = Transaction::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'reference_number',
                        'SO-' . $salesOrder->so_number
                    )
                    ->firstOrFail();

                $entries = LedgerEntry::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        false
                    )
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

                expect(
                    $debitEntry->account->nature
                )->toBe('Customer');

                expect(
                    $creditEntry->account->nature
                )->toBe('Sales');

                expect(
                    (string) $debitEntry->debit_amount
                )->toBe('1000.0000');

                expect(
                    (string) $creditEntry->credit_amount
                )->toBe('1000.0000');

                expect(
                    $transaction->total_debit
                )->toBe('1000.0000');

                expect(
                    $transaction->total_credit
                )->toBe('1000.0000');

                expect(
                    $salesOrder->refresh()->is_accounted
                )->toBeTrue();
            }
        );

        it(
            'is idempotent - multiple calls do not create duplicate entries',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 3,
                    'unit_price' => 50,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = Transaction::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'reference_number',
                        'SO-' . $salesOrder->so_number
                    )
                    ->firstOrFail();

                $countAfterFirst = LedgerEntry::query()
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->count();

                $this->accountingService->onConfirmed(
                    $salesOrder->refresh()
                );

                $countAfterSecond = LedgerEntry::query()
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->count();

                expect($countAfterFirst)
                    ->toBe($countAfterSecond);

                expect($countAfterSecond)->toBe(2);
            }
        );

        it(
            'throws exception if total amount is zero or negative',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );
            }
        )->throws(Exception::class);

        it(
            'creates or uses existing customer A/R account',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $arAccount = Account::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'nature',
                        'Customer'
                    )
                    ->where(
                        'account_name',
                        "AR - {$this->customer->name}"
                    )
                    ->first();

                expect($arAccount)->not->toBeNull();
            }
        );

        it(
            'creates or uses existing Sales Revenue account',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $revenueAccount = Account::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'account_type',
                        'Income'
                    )
                    ->where(
                        'nature',
                        'Sales'
                    )
                    ->first();

                expect($revenueAccount)->not->toBeNull();
            }
        );
    });

    describe('onCancelled', function () {
        it(
            'creates reversal entries when SO is cancelled',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Confirmed',

                    /*
                     * Must be false because onConfirmed() must
                     * create the accounting transaction first.
                     */
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 250,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $salesOrder->refresh();

                expect($salesOrder->is_accounted)->toBeTrue();

                $transaction = Transaction::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'reference_number',
                        'SO-' . $salesOrder->so_number
                    )
                    ->firstOrFail();

                $this->accountingService->onCancelled(
                    $salesOrder
                );

                $originalEntries = LedgerEntry::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        true
                    )
                    ->get();

                $reversalEntries = LedgerEntry::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        false
                    )
                    ->get();

                expect($originalEntries)
                    ->toHaveCount(2);

                expect($reversalEntries)
                    ->toHaveCount(2);

                expect(
                    $salesOrder->refresh()->is_accounted
                )->toBeFalse();

                expect(
                    $transaction->refresh()->isCancelled()
                )->toBeTrue();
            }
        );

        it(
            'reversal entries are opposite of original entries',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Confirmed',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = Transaction::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'reference_number',
                        'SO-' . $salesOrder->so_number
                    )
                    ->firstOrFail();

                $originalEntries = LedgerEntry::query()
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        false
                    )
                    ->get();

                expect($originalEntries)
                    ->toHaveCount(2);

                $originalDebit = $originalEntries
                    ->sum('debit_amount');

                $originalCredit = $originalEntries
                    ->sum('credit_amount');

                $this->accountingService->onCancelled(
                    $salesOrder
                );

                $reversalEntries = LedgerEntry::query()
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        false
                    )
                    ->get();

                $reversedOriginalEntries = LedgerEntry::query()
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->where(
                        'is_reversed',
                        true
                    )
                    ->get();

                expect($reversedOriginalEntries)
                    ->toHaveCount(2);

                expect($reversalEntries)
                    ->toHaveCount(2);

                $reversalDebit = $reversalEntries
                    ->sum('debit_amount');

                $reversalCredit = $reversalEntries
                    ->sum('credit_amount');

                expect(
                    bccomp(
                        (string) $originalDebit,
                        (string) $reversalCredit,
                        4
                    )
                )->toBe(0);

                expect(
                    bccomp(
                        (string) $originalCredit,
                        (string) $reversalDebit,
                        4
                    )
                )->toBe(0);
            }
        );

        it(
            'skips if SO has no accounting entries',
            function () {
                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

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

                $salesOrder = SalesOrder::create([
                    'company_id' => $this->company->id,
                    'customer_id' => $this->customer->id,
                    'so_number' => 'SO-' . rand(1000, 9999),
                    'order_date' => now(),
                    'status' => 'Draft',
                    'is_accounted' => false,
                ]);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_name' => 'Product 1',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]);

                $this->accountingService->onConfirmed(
                    $salesOrder
                );

                $transaction = Transaction::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'reference_number',
                        'SO-' . $salesOrder->so_number
                    )
                    ->firstOrFail();

                $entries = LedgerEntry::query()
                    ->where(
                        'company_id',
                        $this->company->id
                    )
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->count();

                $entriesOther = LedgerEntry::query()
                    ->where(
                        'company_id',
                        $otherCompany->id
                    )
                    ->where(
                        'transaction_id',
                        $transaction->id
                    )
                    ->count();

                expect($entries)->toBe(2);
                expect($entriesOther)->toBe(0);
            }
        );
    });
});