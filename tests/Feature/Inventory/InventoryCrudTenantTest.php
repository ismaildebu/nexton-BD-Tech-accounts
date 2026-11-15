<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Http\Controllers\InventoryController;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);

function inventoryCrudUser(): User
{
    return User::factory()->create([
        'company_id' => null,
        'status' => true,
    ]);
}

it('does not allow updating another company product through tenant scoped binding', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $productB = Product::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $user = inventoryCrudUser();

    $this->actingAs($user)
        ->withSession(['company_id' => $companyA->id])
        ->put(route('inventory.products.update', $productB), [
            'name' => 'Hacked Product',
            'unit' => 'pcs',
            'purchase_price' => 100,
            'sale_price' => 150,
            'reorder_level' => 1,
        ])
        ->assertNotFound();

    expect(
        Product::withoutCompanyScope()->findOrFail($productB->id)->name
    )->toBe('Company B Product');
});

it('does not allow deleting another company product through tenant scoped binding', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $productB = Product::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $user = inventoryCrudUser();

    $this->actingAs($user)
        ->withSession(['company_id' => $companyA->id])
        ->delete(route('inventory.products.destroy', $productB))
        ->assertNotFound();

    expect(
        Product::withoutCompanyScope()->whereKey($productB->id)->exists()
    )->toBeTrue();
});

it('does not allow deleting another company warehouse through tenant scoped binding', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Warehouse',
        'location' => 'B',
    ]);

    $user = inventoryCrudUser();

    $this->actingAs($user)
        ->withSession(['company_id' => $companyA->id])
        ->delete(route('inventory.warehouses.destroy', $warehouseB))
        ->assertNotFound();

    expect(
        Warehouse::withoutCompanyScope()->whereKey($warehouseB->id)->exists()
    )->toBeTrue();
});

it('preserves stock movements when a product is deleted', function (): void {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Test Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Test Warehouse',
        'location' => 'Test',
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    $movement = StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'reference_type' => 'manual',
        'movement_date' => now()->toDateString(),
    ]);

    expect(
        ProductStock::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->exists()
    )->toBeTrue();

    expect(
        StockMovement::withoutGlobalScopes()
            ->whereKey($movement->id)
            ->exists()
    )->toBeTrue();

    $product->delete();

    expect(
        ProductStock::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->exists()
    )->toBeFalse();

    $preservedMovement = StockMovement::withoutGlobalScopes()
        ->find($movement->id);

    expect($preservedMovement)->not->toBeNull()
        ->and($preservedMovement->product_id)->toBeNull()
        ->and($preservedMovement->warehouse_id)->toBe($warehouse->id)
        ->and((float) $preservedMovement->quantity)->toBe(10.0);
});

it('preserves stock movements when a warehouse is deleted', function (): void {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Test Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Test Warehouse',
        'location' => 'Test',
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    $movement = StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'reference_type' => 'manual',
        'movement_date' => now()->toDateString(),
    ]);

    expect(
        ProductStock::withoutGlobalScopes()
            ->where('warehouse_id', $warehouse->id)
            ->exists()
    )->toBeTrue();

    expect(
        StockMovement::withoutGlobalScopes()
            ->whereKey($movement->id)
            ->exists()
    )->toBeTrue();

    $warehouse->delete();

    expect(
        ProductStock::withoutGlobalScopes()
            ->where('warehouse_id', $warehouse->id)
            ->exists()
    )->toBeFalse();

    $preservedMovement = StockMovement::withoutGlobalScopes()
        ->find($movement->id);

    expect($preservedMovement)->not->toBeNull()
        ->and($preservedMovement->product_id)->toBe($product->id)
        ->and($preservedMovement->warehouse_id)->toBeNull()
        ->and((float) $preservedMovement->quantity)->toBe(10.0);
});

it('blocks deleting a product used by a stock-deducted sales order', function (): void {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Protected Product',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Protected Warehouse',
    ]);

    $salesOrder = SalesOrder::create([
        'company_id' => $company->id,
            'customer_id' => Customer::create([
            'company_id' => $company->id,
            'name' => 'Protected Customer',
            'customer_type' => 'Individual',
        ])->id,
        
        'warehouse_id' => $warehouse->id,
        'so_number' => 'SO-TEST-PROTECTED-001',
        'order_date' => now()->toDateString(),
        'status' => 'confirmed',
        'subtotal' => 100,
        'tax' => 0,
        'discount' => 0,
        'total' => 100,
        'is_accounted' => false,
        'is_stock_deducted' => true,
    ]);

    SalesOrderItem::create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $product->id,
        'item_name' => $product->name,
        'quantity' => 1,
        'unit' => 'pcs',
        'unit_price' => 100,
        'total' => 100,
    ]);

    session(['company_id' => $company->id]);

    $controller = app(InventoryController::class);

    $response = $controller->destroyProduct($product);

    expect($response->getSession()->get('error'))
        ->toBe('This product cannot be deleted because it is used by a confirmed Sales Order with deducted stock.');

    expect(
        Product::withoutGlobalScopes()
            ->whereKey($product->id)
            ->exists()
    )->toBeTrue();
});

it('blocks deleting a warehouse used by a stock-deducted sales order', function (): void {
    $company = Company::factory()->create();

    $customer = Customer::create([
        'company_id' => $company->id,
        'name' => 'Protected Customer',
        'customer_type' => 'Individual',
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Protected Warehouse',
    ]);

    $salesOrder = SalesOrder::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'so_number' => 'SO-TEST-PROTECTED-002',
        'order_date' => now()->toDateString(),
        'status' => 'confirmed',
        'subtotal' => 100,
        'tax' => 0,
        'discount' => 0,
        'total' => 100,
        'is_accounted' => false,
        'is_stock_deducted' => true,
    ]);

    session(['company_id' => $company->id]);

    $controller = app(InventoryController::class);

    $response = $controller->destroyWarehouse($warehouse);

    expect($response->getSession()->get('error'))
        ->toBe('This warehouse cannot be deleted because it is used by a confirmed Sales Order with deducted stock.');

    expect(
        Warehouse::withoutGlobalScopes()
            ->whereKey($warehouse->id)
            ->exists()
    )->toBeTrue();
});