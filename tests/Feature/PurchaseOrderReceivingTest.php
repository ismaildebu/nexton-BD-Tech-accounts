<?php

use App\Http\Controllers\PurchaseOrderController;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'company_name' => 'PO Receiving Test Company',
        'business_type' => 'Trading',
    ]);

    session(['company_id' => $this->company->id]);

    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'PO Test User',
        'email' => 'po-test-user@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);

    $this->warehouse = Warehouse::create([
        'company_id' => $this->company->id,
        'name' => 'Main Warehouse',
        'location' => 'Test Location',
    ]);

    $makeProduct = function (string $name, int $companyId): Product {
        return Product::create([
            'company_id' => $companyId,
            'name' => $name,
            'sku' => 'PO-' . uniqid(),
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 0,
            'sale_price' => 0,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
    };

    $this->printer = $makeProduct('Printer', $this->company->id);
    $this->mobile = $makeProduct('Mobile', $this->company->id);

    $payable = Account::create([
        'company_id' => $this->company->id,
        'account_code' => '3001',
        'account_name' => 'Accounts Payable',
        'account_type' => 'Liability',
        'nature' => 'Supplier',
        'opening_balance' => 0,
        'is_active' => true,
    ]);

    $this->vendor = Vendor::create([
        'company_id' => $this->company->id,
        'name' => 'Test Vendor',
        'account_id' => $payable->id,
        'is_active' => true,
    ]);

    /*
     * Create a Purchase Order straight in the database, filling only the
     * columns this installation really has. $items is a list of
     * [product_id, quantity, unit_price].
     */
    $this->makePurchaseOrder = function (
        array $items,
        string $status = 'Confirmed',
        ?int $warehouseId = null
    ): PurchaseOrder {
        $subtotal = '0.0000';

        foreach ($items as [$productId, $quantity, $unitPrice]) {
            $subtotal = bcadd(
                $subtotal,
                bcmul((string) $quantity, (string) $unitPrice, 4),
                4
            );
        }

        $now = now();

        $candidate = [
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'po_number' => 'PO-' . uniqid(),
            'order_date' => $now->toDateString(),
            'po_date' => $now->toDateString(),
            'expected_date' => $now->copy()->addDay()->toDateString(),
            'expected_delivery' => $now->copy()->addDay()->toDateString(),
            'expected_delivery_date' => $now->copy()->addDay()->toDateString(),
            'delivery_date' => $now->copy()->addDay()->toDateString(),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'warehouse_id' => $warehouseId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $columns = array_flip(Schema::getColumnListing('purchase_orders'));

        $id = DB::table('purchase_orders')->insertGetId(
            array_intersect_key($candidate, $columns)
        );

        foreach ($items as [$productId, $quantity, $unitPrice]) {
            DB::table('purchase_order_items')->insert([
                'purchase_order_id' => $id,
                'product_id' => $productId,
                'item_name' => 'Item ' . $productId,
                'quantity' => $quantity,
                'unit' => 'pcs',
                'unit_price' => $unitPrice,
                'total' => bcmul((string) $quantity, (string) $unitPrice, 4),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return PurchaseOrder::findOrFail($id);
    };

    /*
     * Run PurchaseOrderController::updateStatus() directly.
     */
    $this->changeStatus = function (PurchaseOrder $order, string $status): PurchaseOrder {
        session()->forget(['success', 'error']);

        app(PurchaseOrderController::class)->updateStatus(
            Request::create('/', 'POST', ['status' => $status]),
            $order
        );

        return PurchaseOrder::findOrFail($order->id);
    };

    $this->stockOf = function (Product $product, ?int $warehouseId = null): string {
        return (string) (ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId ?? $this->warehouse->id)
            ->value('quantity') ?? '0');
    };
});

describe('Purchase Order receiving', function () {
    it('increases stock for every item when a Confirmed order is received', function () {
        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
            [$this->mobile->id, 12, 5000],
        ]);

        $order = ($this->changeStatus)($order, 'Received');

        expect($order->status)->toBe('Received');
        expect(session('success'))->not->toBeNull();

        expect(bccomp(($this->stockOf)($this->printer), '10', 4))->toBe(0);
        expect(bccomp(($this->stockOf)($this->mobile), '12', 4))->toBe(0);
    });

    it('adds to existing stock instead of replacing it', function () {
        ProductStock::create([
            'company_id' => $this->company->id,
            'product_id' => $this->mobile->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 3,
        ]);

        $order = ($this->makePurchaseOrder)([
            [$this->mobile->id, 12, 5000],
        ]);

        ($this->changeStatus)($order, 'Received');

        expect(bccomp(($this->stockOf)($this->mobile), '15', 4))->toBe(0);
    });

    it('records an in movement with the purchase price as cost', function () {
        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
        ]);

        ($this->changeStatus)($order, 'Received');

        $movement = StockMovement::query()
            ->where('company_id', $this->company->id)
            ->where('reference_type', 'PurchaseOrder')
            ->where('reference_id', $order->id)
            ->first();

        expect($movement)->not->toBeNull();
        expect($movement->type)->toBe('in');
        expect(bccomp((string) $movement->quantity, '10', 4))->toBe(0);
        expect(bccomp((string) $movement->unit_cost, '21000', 4))->toBe(0);
        expect(bccomp((string) $movement->total_cost, '210000', 4))->toBe(0);
    });

    it('does not add stock a second time', function () {
        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
        ]);

        $order = ($this->changeStatus)($order, 'Received');
        $order = ($this->changeStatus)($order, 'Received');

        expect(session('error'))->toContain('already Received');
        expect($order->status)->toBe('Received');
        expect(bccomp(($this->stockOf)($this->printer), '10', 4))->toBe(0);

        expect(
            StockMovement::query()
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $order->id)
                ->count()
        )->toBe(1);
    });

    it('refuses to receive a Draft order and leaves stock alone', function () {
        $order = ($this->makePurchaseOrder)(
            [[$this->printer->id, 10, 21000]],
            'Draft'
        );

        $order = ($this->changeStatus)($order, 'Received');

        expect(session('error'))
            ->toContain('Invalid status change: Draft to Received');

        expect($order->status)->toBe('Draft');
        expect(bccomp(($this->stockOf)($this->printer), '0', 4))->toBe(0);
    });

    it('does not allow a Received order to be cancelled or reopened', function () {
        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
        ]);

        $order = ($this->changeStatus)($order, 'Received');

        $order = ($this->changeStatus)($order, 'Cancelled');
        expect($order->status)->toBe('Received');
        expect(session('error'))->toContain('Invalid status change');

        $order = ($this->changeStatus)($order, 'Confirmed');
        expect($order->status)->toBe('Received');

        expect(bccomp(($this->stockOf)($this->printer), '10', 4))->toBe(0);
    });

    it('rolls back every item when one item cannot be received', function () {
        $otherCompany = Company::create([
            'company_name' => 'Other PO Company',
            'business_type' => 'Trading',
        ]);

        $foreignProduct = Product::create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign Product',
            'sku' => 'FOREIGN-' . uniqid(),
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 0,
            'sale_price' => 0,
            'reorder_level' => 1,
            'is_active' => true,
        ]);

        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
            [$foreignProduct->id, 5, 100],
        ]);

        $order = ($this->changeStatus)($order, 'Received');

        expect(session('error'))->toContain('does not belong to company');
        expect($order->status)->toBe('Confirmed');

        // The first (valid) item must not keep its stock either.
        expect(bccomp(($this->stockOf)($this->printer), '0', 4))->toBe(0);

        expect(
            StockMovement::query()
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $order->id)
                ->count()
        )->toBe(0);
    });

    it('refuses to guess when the company has several warehouses and the order has none', function () {
        Warehouse::create([
            'company_id' => $this->company->id,
            'name' => 'Second Warehouse',
            'location' => 'Other Location',
        ]);

        $order = ($this->makePurchaseOrder)([
            [$this->printer->id, 10, 21000],
        ]);

        $order = ($this->changeStatus)($order, 'Received');

        if (Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            expect(session('error'))->toContain('no warehouse');
            expect($order->status)->toBe('Confirmed');
            expect(bccomp(($this->stockOf)($this->printer), '0', 4))->toBe(0);
        } else {
            expect(session('error'))->toContain('more than one');
            expect($order->status)->toBe('Confirmed');
        }
    });

    it('uses the warehouse stored on the purchase order when it has one', function () {
        if (! Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            $this->markTestSkipped('purchase_orders has no warehouse_id column.');
        }

        $second = Warehouse::create([
            'company_id' => $this->company->id,
            'name' => 'Second Warehouse',
            'location' => 'Other Location',
        ]);

        $order = ($this->makePurchaseOrder)(
            [[$this->printer->id, 10, 21000]],
            'Confirmed',
            $second->id
        );

        ($this->changeStatus)($order, 'Received');

        expect(bccomp(($this->stockOf)($this->printer, $second->id), '10', 4))->toBe(0);
        expect(bccomp(($this->stockOf)($this->printer, $this->warehouse->id), '0', 4))->toBe(0);
    });
});