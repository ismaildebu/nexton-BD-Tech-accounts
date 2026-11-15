<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createInventoryCompany(): Company
{
    return Company::factory()->create();
}

function createInventoryProduct(Company $company): Product
{
    return Product::create([
        'company_id' => $company->id,
        'name' => 'Test Product',
        'sku' => 'TEST-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);
}

function createInventoryWarehouse(Company $company): Warehouse
{
    return Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Test Warehouse',
        'location' => 'Test Location',
    ]);
}

it('stores product stock with the correct company context', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    $stock = ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    expect($stock->company_id)->toBe($company->id)
        ->and($stock->product_id)->toBe($product->id)
        ->and($stock->warehouse_id)->toBe($warehouse->id)
        ->and((float) $stock->quantity)->toBe(10.0);
});

it('increases stock atomically for stock in', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    DB::transaction(function () use ($product, $warehouse): void {
        StockMovement::adjustStock(
            $product->id,
            $warehouse->id,
            2,
            'in'
        );
    });

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity')
    )->toBe(5.0);
});

it('decreases stock atomically for stock out', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    DB::transaction(function () use ($product, $warehouse): void {
        StockMovement::adjustStock(
            $product->id,
            $warehouse->id,
            2,
            'out'
        );
    });

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity')
    )->toBe(3.0);
});

it('rejects stock out when available stock is insufficient', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    expect(function () use ($product, $warehouse): void {
        DB::transaction(function () use ($product, $warehouse): void {
            StockMovement::adjustStock(
                $product->id,
                $warehouse->id,
                4,
                'out'
            );
        });
    })->toThrow(
        RuntimeException::class,
        'Insufficient stock.'
    );

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity')
    )->toBe(3.0);
});

it('rolls back stock movement and stock adjustment together', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    $initialMovementCount = StockMovement::query()->count();

    expect(function () use ($company, $product, $warehouse): void {
        DB::transaction(function () use ($company, $product, $warehouse): void {
            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'out',
                'quantity' => 1,
                'unit_cost' => 100,
                'total_cost' => 100,
                'reference_type' => 'test',
                'movement_date' => now()->toDateString(),
            ]);

            StockMovement::adjustStock(
                $product->id,
                $warehouse->id,
                1,
                'out'
            );

            throw new RuntimeException('ROLLBACK_TEST');
        });
    })->toThrow(RuntimeException::class, 'ROLLBACK_TEST');

    expect(StockMovement::query()->count())
        ->toBe($initialMovementCount);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity')
    )->toBe(3.0);
});

it('does not expose another company product stock through the tenant scope', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productA = createInventoryProduct($companyA);
    $warehouseA = createInventoryWarehouse($companyA);

    $productB = createInventoryProduct($companyB);
    $warehouseB = createInventoryWarehouse($companyB);

    ProductStock::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'quantity' => 10,
    ]);

    ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'quantity' => 20,
    ]);

    session(['company_id' => $companyA->id]);

    expect(ProductStock::query()->count())
        ->toBe(1);

    expect(ProductStock::query()->first()->company_id)
        ->toBe($companyA->id);

    session(['company_id' => $companyB->id]);

    expect(ProductStock::query()->count())
        ->toBe(1);

    expect(ProductStock::query()->first()->company_id)
        ->toBe($companyB->id);
});

it('allows the same product and warehouse combination in different companies', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productA = createInventoryProduct($companyA);
    $warehouseA = createInventoryWarehouse($companyA);

    $productB = createInventoryProduct($companyB);
    $warehouseB = createInventoryWarehouse($companyB);

    session(['company_id' => $companyA->id]);

    $stockA = ProductStock::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'quantity' => 5,
    ]);

    session(['company_id' => $companyB->id]);

    $stockB = ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'quantity' => 7,
    ]);

    expect($stockA->id)->not->toBe($stockB->id)
        ->and($stockA->company_id)->toBe($companyA->id)
        ->and($stockB->company_id)->toBe($companyB->id);
});

it('requires the current company context for stock adjustment', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);
    $warehouse = createInventoryWarehouse($company);

    expect(function () use ($product, $warehouse): void {
        StockMovement::adjustStock(
            $product->id,
            $warehouse->id,
            1,
            'in'
        );
    })->toThrow(
        RuntimeException::class,
        'Company context is required to adjust stock.'
    );
});

it('rejects stock in using another company warehouse or product', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productA = createInventoryProduct($companyA);
    $warehouseA = createInventoryWarehouse($companyA);

    $productB = createInventoryProduct($companyB);
    $warehouseB = createInventoryWarehouse($companyB);

    session(['company_id' => $companyA->id]);

    $request = Illuminate\Http\Request::create(
        '/inventory/stock-in',
        'POST',
        [
            'warehouse_id' => $warehouseB->id,
            'movement_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $productB->id,
                    'quantity' => 2,
                    'unit_cost' => 100,
                ],
            ],
        ]
    );

    expect(function () use ($request): void {
        app(\App\Http\Controllers\InventoryController::class)
            ->storeStockIn($request);
    })->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(ProductStock::query()->count())->toBe(0);
    expect(StockMovement::query()->count())->toBe(0);
});
it('rejects stock out using another company warehouse or product', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productB = createInventoryProduct($companyB);
    $warehouseB = createInventoryWarehouse($companyB);

    ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'quantity' => 10,
    ]);

    session(['company_id' => $companyA->id]);

    $request = Illuminate\Http\Request::create(
        '/inventory/stock-out',
        'POST',
        [
            'warehouse_id' => $warehouseB->id,
            'movement_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $productB->id,
                    'quantity' => 2,
                ],
            ],
        ]
    );

    expect(function () use ($request): void {
        app(\App\Http\Controllers\InventoryController::class)
            ->storeStockOut($request);
    })->toThrow(\Illuminate\Validation\ValidationException::class);

    // Switch to Company B to verify its stock was not changed.
    session(['company_id' => $companyB->id]);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $productB->id)
            ->where('warehouse_id', $warehouseB->id)
            ->value('quantity')
    )->toBe(10.0);

    expect(StockMovement::query()->count())->toBe(0);
});

it('records stock in movement and increases stock atomically', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company, [
        'purchase_price' => 250,
    ]);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    $request = Illuminate\Http\Request::create(
        '/inventory/stock-in',
        'POST',
        [
            'warehouse_id' => $warehouse->id,
            'movement_date' => now()->toDateString(),
            'reference' => 'TEST-IN-001',
            'reference_type' => 'manual',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_cost' => 250,
                ],
            ],
        ]
    );

    app(\App\Http\Controllers\InventoryController::class)
        ->storeStockIn($request);

    expect((float) ProductStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->value('quantity'))->toBe(4.0);

    $movement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('in')
        ->and((float) $movement->quantity)->toBe(4.0)
        ->and((float) $movement->unit_cost)->toBe(250.0)
        ->and((float) $movement->total_cost)->toBe(1000.0);
});

it('records stock out movement and decreases stock atomically', function (): void {
    $company = createInventoryCompany();
    
    $product = Product::create([
    'company_id' => $company->id,
    'name' => 'Stock Out Product',
    'sku' => 'OUT-' . uniqid(),
    'category' => 'General',
    'unit' => 'pcs',
    'purchase_price' => 300,
    'sale_price' => 450,
    'reorder_level' => 1,
    'is_active' => true,
]);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    $request = Illuminate\Http\Request::create(
        '/inventory/stock-out',
        'POST',
        [
            'warehouse_id' => $warehouse->id,
            'movement_date' => now()->toDateString(),
            'reference' => 'TEST-OUT-001',
            'reference_type' => 'manual',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ]
    );

    app(\App\Http\Controllers\InventoryController::class)
        ->storeStockOut($request);

    expect((float) ProductStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->value('quantity'))->toBe(6.0);

    $movement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('out')
        ->and((float) $movement->quantity)->toBe(4.0)
        ->and((float) $movement->unit_cost)->toBe(300.0)
        ->and((float) $movement->total_cost)->toBe(1200.0);
});

it('rolls back stock and movement when stock in transaction fails', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company, [
        'purchase_price' => 100,
    ]);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    expect(function () use ($product, $warehouse, $company): void {
        DB::transaction(function () use ($product, $warehouse, $company): void {
            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => 5,
                'unit_cost' => 100,
                'total_cost' => 500,
                'movement_date' => now()->toDateString(),
            ]);

            StockMovement::adjustStock(
                $product->id,
                $warehouse->id,
                5,
                'in'
            );

            throw new RuntimeException('Forced rollback test.');
        });
    })->toThrow(
        RuntimeException::class,
        'Forced rollback test.'
    );

    expect(ProductStock::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);
});

it('rolls back stock and movement when stock out transaction fails', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company, [
        'purchase_price' => 150,
    ]);
    $warehouse = createInventoryWarehouse($company);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    expect(function () use ($product, $warehouse, $company): void {
        DB::transaction(function () use ($product, $warehouse, $company): void {
            StockMovement::adjustStock(
                $product->id,
                $warehouse->id,
                4,
                'out'
            );

            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'out',
                'quantity' => 4,
                'unit_cost' => 150,
                'total_cost' => 600,
                'movement_date' => now()->toDateString(),
            ]);

            throw new RuntimeException('Forced rollback test.');
        });
    })->toThrow(
        RuntimeException::class,
        'Forced rollback test.'
    );

    expect((float) ProductStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->value('quantity'))->toBe(10.0);

    expect(StockMovement::query()->count())->toBe(0);
});

it('rejects direct stock movement creation across company boundaries', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productB = createInventoryProduct($companyB);

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $companyA->id]);

    expect(fn () => StockMovement::create([
        'company_id' => $companyA->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'reference' => 'cross-company-test',
        'reference_type' => 'manual',
        'movement_date' => now()->toDateString(),
    ]))->toThrow(\RuntimeException::class);
});

it('rejects stock movement with non-positive quantity', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Quantity Test Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    expect(fn () => StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 0,
        'unit_cost' => 100,
        'total_cost' => 0,
        'movement_date' => now()->toDateString(),
    ]))->toThrow(\RuntimeException::class);
});

it('rejects stock movement with negative unit cost', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Cost Test Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    expect(fn () => StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => -100,
        'total_cost' => -1000,
        'movement_date' => now()->toDateString(),
    ]))->toThrow(\RuntimeException::class);
});

it('rejects stock movement with incorrect total cost', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Total Cost Test Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    expect(fn () => StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 500,
        'movement_date' => now()->toDateString(),
    ]))->toThrow(\RuntimeException::class);
});

it('rejects stock movement with invalid type', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Type Test Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    expect(fn () => StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'invalid',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'movement_date' => now()->toDateString(),
    ]))->toThrow(\RuntimeException::class);
});

it('rejects invalid stock movement updates', function (): void {
    $company = createInventoryCompany();
    $product = createInventoryProduct($company);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Update Test Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    $movement = StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'movement_date' => now()->toDateString(),
    ]);

    expect(fn () => $movement->update([
        'quantity' => 0,
    ]))->toThrow(\RuntimeException::class);

    expect(fn () => $movement->update([
        'unit_cost' => -50,
    ]))->toThrow(\RuntimeException::class);

    expect(fn () => $movement->update([
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 500,
    ]))->toThrow(\RuntimeException::class);

    expect(fn () => $movement->update([
        'type' => 'invalid',
    ]))->toThrow(\RuntimeException::class);
});

it('does not expose another company stock movements in the movements report', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productA = createInventoryProduct($companyA);
    $productB = createInventoryProduct($companyB);

    $warehouseA = Warehouse::create([
        'company_id' => $companyA->id,
        'name' => 'Company A Warehouse',
        'location' => 'Test',
    ]);

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Warehouse',
        'location' => 'Test',
    ]);

    StockMovement::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'reference' => 'COMPANY-A-MOVEMENT',
        'reference_type' => 'manual',
        'movement_date' => now()->toDateString(),
    ]);

    StockMovement::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'type' => 'in',
        'quantity' => 20,
        'unit_cost' => 200,
        'total_cost' => 4000,
        'reference' => 'COMPANY-B-MOVEMENT',
        'reference_type' => 'manual',
        'movement_date' => now()->toDateString(),
    ]);

    $userA = \App\Models\User::factory()->create([
        'company_id' => $companyA->id,
        'status' => true,
    ]);

    $userA->syncRoles(['admin']);

    session(['company_id' => $companyA->id]);

    $response = $this->actingAs($userA)
        ->get(route('inventory.movements'));

    $response->assertOk()
        ->assertSee('COMPANY-A-MOVEMENT')
        ->assertDontSee('COMPANY-B-MOVEMENT');
});

it('does not include another company stock in the stock report', function (): void {
    $companyA = createInventoryCompany();
    $companyB = createInventoryCompany();

    $productA = createInventoryProduct($companyA);
    $productB = createInventoryProduct($companyB);

    $warehouseA = Warehouse::create([
        'company_id' => $companyA->id,
        'name' => 'Company A Warehouse',
        'location' => 'Test',
    ]);

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Warehouse',
        'location' => 'Test',
    ]);

    ProductStock::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'quantity' => 10,
    ]);

    ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'quantity' => 50,
    ]);

    $userA = \App\Models\User::factory()->create([
        'company_id' => $companyA->id,
        'status' => true,
    ]);

    $userA->syncRoles(['admin']);

    session(['company_id' => $companyA->id]);

    $response = $this->actingAs($userA)
        ->get(route('inventory.stock-report'));

    $response->assertOk()
        ->assertViewHas('products', function ($products) use ($productA, $productB) {
            return $products->contains('id', $productA->id)
                && ! $products->contains('id', $productB->id);
        });
});
