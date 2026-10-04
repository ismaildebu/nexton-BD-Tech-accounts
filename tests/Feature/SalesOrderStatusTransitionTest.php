<?php

use App\Http\Controllers\SalesOrderController;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoucherType;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'company_name' => 'Transition Test Company',
        'business_type' => 'Trading',
    ]);

    FinancialYear::create([
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

    $this->customer = Customer::create([
        'company_id' => $this->company->id,
        'name' => 'Transition Customer',
        'email' => 'transition@example.com',
        'phone' => '01234567890',
        'address' => '123 Test Street',
        'customer_type' => 'Individual',
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'company_id' => $this->company->id,
        'name' => 'Transition Product',
        'sku' => 'TRN-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'company_id' => $this->company->id,
        'name' => 'Transition Warehouse',
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
        'name' => 'Transition User',
        'email' => 'transition-user@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);

    /*
     * A Draft order: 2 x 100 = 200 (no tax, no discount).
     */
    $this->makeOrder = function (): SalesOrder {
        $order = SalesOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'so_number' => 'SO-' . uniqid(),
            'order_date' => now(),
            'status' => 'Draft',
            'subtotal' => '200.0000',
            'tax' => '0.0000',
            'discount' => '0.0000',
            'total' => '200.0000',
            'is_accounted' => false,
            'is_stock_deducted' => false,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'item_name' => 'Transition Item',
            'quantity' => 2,
            'unit_price' => 100,
            'total' => '200.0000',
        ]);

        return $order;
    };

    /*
     * Call SalesOrderController::updateStatus() directly.
     * Returns the freshly loaded order.
     */
    $this->changeStatus = function (
        SalesOrder $order,
        string $status
    ): SalesOrder {
        session()->forget(['success', 'error']);

        app(SalesOrderController::class)->updateStatus(
            Request::create('/', 'POST', ['status' => $status]),
            $order
        );

        return $order->fresh();
    };

    $this->stockQuantity = function (): string {
        return (string) ProductStock::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
    };
});

describe('SalesOrderController::updateStatus', function () {
    it('confirms a Draft order and posts its accounting', function () {
        $order = ($this->makeOrder)();

        $order = ($this->changeStatus)($order, 'Confirmed');

        expect($order->status)->toBe('Confirmed');
        expect($order->is_accounted)->toBeTrue();
        expect($order->is_stock_deducted)->toBeTrue();
        expect(session('success'))->not->toBeNull();

        expect(bccomp(($this->stockQuantity)(), '98', 4))->toBe(0);
    });

    it('rejects Confirmed to Draft and keeps the accounting intact', function () {
        $order = ($this->makeOrder)();
        $order = ($this->changeStatus)($order, 'Confirmed');

        $order = ($this->changeStatus)($order, 'Draft');

        expect(session('error'))
            ->toContain('Invalid status change: Confirmed to Draft');

        expect($order->status)->toBe('Confirmed');
        expect($order->is_accounted)->toBeTrue();
        expect(bccomp(($this->stockQuantity)(), '98', 4))->toBe(0);
    });

    it('rejects Draft to Delivered without touching stock or accounting', function () {
        $order = ($this->makeOrder)();

        $order = ($this->changeStatus)($order, 'Delivered');

        expect(session('error'))
            ->toContain('Invalid status change: Draft to Delivered');

        expect($order->status)->toBe('Draft');
        expect($order->is_accounted)->toBeFalse();
        expect(bccomp(($this->stockQuantity)(), '100', 4))->toBe(0);
    });

    it('rejects Cancelled to Confirmed and creates no new accounting', function () {
        $order = ($this->makeOrder)();
        $order = ($this->changeStatus)($order, 'Confirmed');
        $order = ($this->changeStatus)($order, 'Cancelled');

        expect($order->status)->toBe('Cancelled');
        expect(bccomp(($this->stockQuantity)(), '100', 4))->toBe(0);

        $order = ($this->changeStatus)($order, 'Confirmed');

        expect(session('error'))
            ->toContain('Invalid status change: Cancelled to Confirmed');

        expect($order->status)->toBe('Cancelled');
        expect($order->is_accounted)->toBeFalse();
        expect(bccomp(($this->stockQuantity)(), '100', 4))->toBe(0);

        // Only the original (now cancelled) voucher exists.
        expect(
            Transaction::query()
                ->where('company_id', $this->company->id)
                ->where('reference_type', 'SalesOrder')
                ->where('reference_id', $order->id)
                ->count()
        )->toBe(1);
    });

    it('rejects a change to the same status', function () {
        $order = ($this->makeOrder)();
        $order = ($this->changeStatus)($order, 'Confirmed');

        $order = ($this->changeStatus)($order, 'Confirmed');

        expect(session('error'))->toContain('already Confirmed');
        expect($order->status)->toBe('Confirmed');
    });

    it('allows Confirmed to Delivered', function () {
        $order = ($this->makeOrder)();
        $order = ($this->changeStatus)($order, 'Confirmed');

        $order = ($this->changeStatus)($order, 'Delivered');

        expect($order->status)->toBe('Delivered');
        expect($order->is_accounted)->toBeTrue();
        expect(bccomp(($this->stockQuantity)(), '98', 4))->toBe(0);
    });

    it('reverses accounting and restores stock when a Delivered order is cancelled', function () {
        $order = ($this->makeOrder)();
        $order = ($this->changeStatus)($order, 'Confirmed');
        $order = ($this->changeStatus)($order, 'Delivered');

        $order = ($this->changeStatus)($order, 'Cancelled');

        expect($order->status)->toBe('Cancelled');
        expect($order->is_accounted)->toBeFalse();
        expect($order->is_stock_deducted)->toBeFalse();
        expect(bccomp(($this->stockQuantity)(), '100', 4))->toBe(0);
    });
});