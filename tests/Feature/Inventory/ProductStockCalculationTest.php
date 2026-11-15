<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates total stock across multiple warehouses', function () {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Multi Warehouse Product',
        'unit' => 'pcs',
        'purchase_price' => 500,
        'sale_price' => 700,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $warehouseA = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Warehouse A',
    ]);

    $warehouseB = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Warehouse B',
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouseA->id,
        'quantity' => 7,
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouseB->id,
        'quantity' => 8,
    ]);

    session(['company_id' => $company->id]);

    $product->load('stocks');

    expect($product->totalStock())->toBe(15.0)
        ->and($product->stockValue())->toBe(7500.0)
        ->and($product->isLowStock())->toBeFalse();
});

it('does not include another company stock in total stock', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $productA = Product::create([
        'company_id' => $companyA->id,
        'name' => 'Company A Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $productB = Product::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $warehouseA = Warehouse::create([
        'company_id' => $companyA->id,
        'name' => 'Warehouse A',
    ]);

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Warehouse B',
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
        'quantity' => 100,
    ]);

    session(['company_id' => $companyA->id]);

    $productA->load('stocks');

    expect($productA->totalStock())->toBe(10.0)
        ->and($productA->stockValue())->toBe(1000.0);
});

it('detects low stock at the reorder level', function () {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Low Stock Product',
        'unit' => 'pcs',
        'purchase_price' => 200,
        'sale_price' => 300,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Warehouse',
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    session(['company_id' => $company->id]);

    $product->load('stocks');

    expect($product->totalStock())->toBe(5.0)
        ->and($product->isLowStock())->toBeTrue();
});
