<?php

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\VoucherType;
use App\Models\User;
use App\Services\SalesOrderAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create company
    $this->company = Company::create([
        'company_name' => 'Test Company LLC',
        'business_type' => 'Trading',
    ]);

    // Create financial year
    FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => 'FY ' . date('Y') . '-' . (date('Y') + 1),
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'is_active' => true,
    ]);

    // Create voucher type (SV - Sales Voucher)
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

    // Create customer
    $this->customer = Customer::create([
        'company_id' => $this->company->id,
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '01234567890',
        'address' => '123 Test Street',
        'customer_type' => 'Individual',
        'is_active' => true,
    ]);

    // Create authenticated user for accounting entries (audit trail)
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

        it('creates double-entry journal when SO is confirmed', function () {
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

            $this->accountingService->onConfirmed($salesOrder);

            $entries = LedgerEntry::where('company_id', $this->company->id)
                ->where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->where('is_reversed', false)
                ->get();

            expect($entries)->toHaveCount(2);
            expect($entries->sum('debit_amount'))->toBe($entries->sum('credit_amount'));

            $debitEntry = $entries->where('debit_amount', '>', 0)->first();
            $creditEntry = $entries->where('credit_amount', '>', 0)->first();

            expect($debitEntry->account->nature)->toBe('Customer');
            expect($creditEntry->account->nature)->toBe('Sales');
            expect($debitEntry->debit_amount)->toBe(1000.0);
            expect($creditEntry->credit_amount)->toBe(1000.0);
            expect($salesOrder->refresh()->is_accounted)->toBeTrue();
        });

        it('is idempotent - multiple calls do not create duplicate entries', function () {
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

            $this->accountingService->onConfirmed($salesOrder);
            $countAfterFirst = LedgerEntry::where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->count();

            $this->accountingService->onConfirmed($salesOrder);
            $countAfterSecond = LedgerEntry::where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->count();

            expect($countAfterFirst)->toBe($countAfterSecond);
        });

        it('throws exception if total amount is zero or negative', function () {
            $salesOrder = SalesOrder::create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'so_number' => 'SO-' . rand(1000, 9999),
                'order_date' => now(),
                'status' => 'Draft',
                'is_accounted' => false,
            ]);

            $this->accountingService->onConfirmed($salesOrder);
        })->throws(Exception::class);

        it('creates or uses existing customer A/R account', function () {
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

            $this->accountingService->onConfirmed($salesOrder);

            $arAccount = Account::where('company_id', $this->company->id)
                ->where('nature', 'Customer')
                ->where('account_name', "AR - {$this->customer->name}")
                ->first();

            expect($arAccount)->not->toBeNull();
        });

        it('creates or uses existing Sales Revenue account', function () {
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

            $this->accountingService->onConfirmed($salesOrder);

            $revenueAccount = Account::where('company_id', $this->company->id)
                ->where('account_type', 'Income')
                ->where('nature', 'Sales')
                ->first();

            expect($revenueAccount)->not->toBeNull();
        });

    });

    describe('onCancelled', function () {

        it('creates reversal entries when SO is cancelled', function () {
            $salesOrder = SalesOrder::create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'so_number' => 'SO-' . rand(1000, 9999),
                'order_date' => now(),
                'status' => 'Confirmed',
                'is_accounted' => true,
            ]);

            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'item_name' => 'Product 1',
                'quantity' => 2,
                'unit_price' => 250,
            ]);

            $this->accountingService->onConfirmed($salesOrder);
            $this->accountingService->onCancelled($salesOrder);

            $reversalEntries = LedgerEntry::where('company_id', $this->company->id)
                ->where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->where('is_reversed', true)
                ->get();

            expect($reversalEntries)->toHaveCount(2);
            expect($salesOrder->refresh()->is_accounted)->toBeFalse();
        });

        it('reversal entries are opposite of original entries', function () {
            $salesOrder = SalesOrder::create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'so_number' => 'SO-' . rand(1000, 9999),
                'order_date' => now(),
                'status' => 'Confirmed',
                'is_accounted' => true,
            ]);

            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'item_name' => 'Product 1',
                'quantity' => 1,
                'unit_price' => 1000,
            ]);

            $this->accountingService->onConfirmed($salesOrder);
            $this->accountingService->onCancelled($salesOrder);

            $debitOriginal = LedgerEntry::where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->where('is_reversed', false)
                ->sum('debit_amount');

            $creditReversal = LedgerEntry::where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->where('is_reversed', true)
                ->sum('credit_amount');

            expect($debitOriginal)->toBe($creditReversal);
        });

        it('skips if SO has no accounting entries', function () {
            $salesOrder = SalesOrder::create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'so_number' => 'SO-' . rand(1000, 9999),
                'order_date' => now(),
                'status' => 'Draft',
                'is_accounted' => false,
            ]);

            $this->accountingService->onCancelled($salesOrder);
            expect(true)->toBeTrue();
        });

    });

    describe('company scoping', function () {

        it('entries belong only to specified company', function () {
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

            $this->accountingService->onConfirmed($salesOrder);

            $entries = LedgerEntry::where('company_id', $this->company->id)
                ->where('reference_type', 'SalesOrder')
                ->where('reference_id', $salesOrder->id)
                ->count();

            $entriesOther = LedgerEntry::where('company_id', $otherCompany->id)
                ->where('reference_type', 'SalesOrder')
                ->count();

            expect($entries)->toBe(2);
            expect($entriesOther)->toBe(0);
        });

    });

});