<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTransferCompany(): Company
{
    return Company::factory()->create();
}

function createTransferProduct(Company $company): Product
{
    return Product::create([
        'company_id' => $company->id,
        'name' => 'Transfer Product',
        'sku' => 'TRANSFER-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);
}

function createTransferWarehouse(Company $company, string $name): Warehouse
{
    return Warehouse::create([
        'company_id' => $company->id,
        'name' => $name,
        'location' => 'Test Location',
    ]);
}

it('transfers stock atomically between warehouses', function (): void {
    $company = createTransferCompany();
    $product = createTransferProduct($company);

    $source = createTransferWarehouse($company, 'Source Warehouse');
    $destination = createTransferWarehouse($company, 'Destination Warehouse');

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $source->id,
        'quantity' => 10,
    ]);

    $request = Illuminate\Http\Request::create(
        '/stock-transfers',
        'POST',
        [
            'product_id' => $product->id,
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'quantity' => 4,
            'transfer_date' => now()->toDateString(),
        ]
    );

    $response = app(\App\Http\Controllers\StockTransferController::class)
        ->store($request);

    expect($response->isRedirect())->toBeTrue();

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $source->id)
            ->value('quantity')
    )->toBe(6.0);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $destination->id)
            ->value('quantity')
    )->toBe(4.0);

    expect(StockTransfer::query()->count())->toBe(1);
    expect(StockMovement::query()->count())->toBe(2);
});

it('rejects transfer when source stock is insufficient', function (): void {
    $company = createTransferCompany();
    $product = createTransferProduct($company);

    $source = createTransferWarehouse($company, 'Source Warehouse');
    $destination = createTransferWarehouse($company, 'Destination Warehouse');

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $source->id,
        'quantity' => 3,
    ]);

    $request = Illuminate\Http\Request::create(
        '/stock-transfers',
        'POST',
        [
            'product_id' => $product->id,
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'quantity' => 5,
            'transfer_date' => now()->toDateString(),
        ]
    );

    $response = app(\App\Http\Controllers\StockTransferController::class)
        ->store($request);

    expect($response->isRedirect())->toBeTrue();

    expect(StockTransfer::query()->count())->toBe(0);
    expect(StockMovement::query()->count())->toBe(0);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $source->id)
            ->value('quantity')
    )->toBe(3.0);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $destination->id)
            ->value('quantity')
    )->toBe(0.0);
});

it('does not expose another company transfer records through the tenant scope', function (): void {
    $companyA = createTransferCompany();
    $companyB = createTransferCompany();

    $productA = createTransferProduct($companyA);
    $sourceA = createTransferWarehouse($companyA, 'Source A');
    $destinationA = createTransferWarehouse($companyA, 'Destination A');

    $productB = createTransferProduct($companyB);
    $sourceB = createTransferWarehouse($companyB, 'Source B');
    $destinationB = createTransferWarehouse($companyB, 'Destination B');

    session(['company_id' => $companyA->id]);

    StockTransfer::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'from_warehouse_id' => $sourceA->id,
        'to_warehouse_id' => $destinationA->id,
        'quantity' => 2,
        'transfer_date' => now()->toDateString(),
    ]);

    session(['company_id' => $companyB->id]);

    StockTransfer::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'from_warehouse_id' => $sourceB->id,
        'to_warehouse_id' => $destinationB->id,
        'quantity' => 3,
        'transfer_date' => now()->toDateString(),
    ]);

    expect(StockTransfer::query()->count())->toBe(1);
    expect(StockTransfer::query()->first()->company_id)
        ->toBe($companyB->id);
});

it('rejects transfer using another company product or warehouse', function (): void {
    $companyA = createTransferCompany();
    $companyB = createTransferCompany();

    $productA = createTransferProduct($companyA);
    $sourceA = createTransferWarehouse($companyA, 'Source A');
    $destinationA = createTransferWarehouse($companyA, 'Destination A');

    $productB = createTransferProduct($companyB);
    $sourceB = createTransferWarehouse($companyB, 'Source B');

    session(['company_id' => $companyA->id]);

    ProductStock::create([
        'company_id' => $companyA->id,
        'product_id' => $productA->id,
        'warehouse_id' => $sourceA->id,
        'quantity' => 10,
    ]);

    $request = Illuminate\Http\Request::create(
        '/stock-transfers',
        'POST',
        [
            'product_id' => $productB->id,
            'from_warehouse_id' => $sourceB->id,
            'to_warehouse_id' => $destinationA->id,
            'quantity' => 2,
            'transfer_date' => now()->toDateString(),
        ]
    );

    expect(function () use ($request): void {
        app(\App\Http\Controllers\StockTransferController::class)
            ->store($request);
    })->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(StockTransfer::query()->count())->toBe(0);
    expect(StockMovement::query()->count())->toBe(0);
});

it('reverses stock and movements when a transfer is deleted', function (): void {
    $company = createTransferCompany();
    $product = createTransferProduct($company);

    $source = createTransferWarehouse($company, 'Source Warehouse');
    $destination = createTransferWarehouse($company, 'Destination Warehouse');

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $source->id,
        'quantity' => 10,
    ]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $destination->id,
        'quantity' => 0,
    ]);

    $transfer = StockTransfer::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'quantity' => 4,
        'transfer_date' => now()->toDateString(),
    ]);

    ProductStock::where('product_id', $product->id)
        ->where('warehouse_id', $source->id)
        ->decrement('quantity', 4);

    ProductStock::where('product_id', $product->id)
        ->where('warehouse_id', $destination->id)
        ->increment('quantity', 4);

    StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $source->id,
        'type' => 'transfer',
        'quantity' => 4,
        'unit_cost' => 100,
        'total_cost' => 400,
        'reference' => "Transfer #{$transfer->id}",
        'reference_type' => 'transfer',
        'reference_id' => $transfer->id,
        'movement_date' => now()->toDateString(),
    ]);

    StockMovement::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $destination->id,
        'type' => 'transfer',
        'quantity' => 4,
        'unit_cost' => 100,
        'total_cost' => 400,
        'reference' => "Transfer #{$transfer->id}",
        'reference_type' => 'transfer',
        'reference_id' => $transfer->id,
        'movement_date' => now()->toDateString(),
    ]);

    $response = app(\App\Http\Controllers\StockTransferController::class)
        ->destroy($transfer);

    expect($response->isRedirect())->toBeTrue();

    expect(StockTransfer::query()->count())->toBe(0);
    expect(StockMovement::query()->count())->toBe(0);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $source->id)
            ->value('quantity')
    )->toBe(10.0);

    expect(
        (float) ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $destination->id)
            ->value('quantity')
    )->toBe(0.0);
});

it('creates destination stock with the correct company context during transfer', function (): void {
    $company = Company::factory()->create();

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Transfer Test Product',
        'sku' => 'TR-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $sourceWarehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Source Warehouse',
        'location' => 'Test',
    ]);

    $destinationWarehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Destination Warehouse',
        'location' => 'Test',
    ]);

    session(['company_id' => $company->id]);

    ProductStock::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'warehouse_id' => $sourceWarehouse->id,
        'quantity' => 20,
    ]);

    $user = \App\Models\User::factory()->create([
        'company_id' => $company->id,
    ]);
    $user->givePermissionTo('stock.transfer');

    session(['company_id' => $company->id]);

    $this->actingAs($user)
        ->post(route('stock-transfers.store'), [
            'product_id' => $product->id,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destinationWarehouse->id,
            'quantity' => 5,
            'transfer_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $destinationStock = ProductStock::withoutCompanyScope()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $destinationWarehouse->id)
        ->first();

    expect($destinationStock)->not->toBeNull()
        ->and($destinationStock->company_id)->toBe($company->id)
        ->and((float) $destinationStock->quantity)->toBe(5.0);
});



it('does not allow deleting another company stock transfer', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $productB = Product::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Product',
        'sku' => 'B-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $sourceB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Source',
        'location' => 'Test',
    ]);

    $destinationB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Destination',
        'location' => 'Test',
    ]);

    $transferB = StockTransfer::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'from_warehouse_id' => $sourceB->id,
        'to_warehouse_id' => $destinationB->id,
        'quantity' => 5,
        'transfer_date' => now()->toDateString(),
    ]);

    $userA = \App\Models\User::factory()->create([
        'company_id' => $companyA->id,
    ]);

    session(['company_id' => $companyA->id]);

    $this->actingAs($userA)
        ->delete(route('stock-transfers.destroy', $transferB))
        ->assertNotFound();

    expect(
        StockTransfer::withoutCompanyScope()->find($transferB->id)
    )->not->toBeNull();
});

it('reverses only the transfer company stock when deleting a transfer', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $productB = Product::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Product',
        'sku' => 'B-' . uniqid(),
        'category' => 'General',
        'unit' => 'pcs',
        'purchase_price' => 100,
        'sale_price' => 150,
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    $sourceB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Source',
        'location' => 'Test',
    ]);

    $destinationB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Destination',
        'location' => 'Test',
    ]);

    ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $sourceB->id,
        'quantity' => 10,
    ]);

    ProductStock::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'warehouse_id' => $destinationB->id,
        'quantity' => 5,
    ]);

    $transferB = StockTransfer::create([
        'company_id' => $companyB->id,
        'product_id' => $productB->id,
        'from_warehouse_id' => $sourceB->id,
        'to_warehouse_id' => $destinationB->id,
        'quantity' => 5,
        'transfer_date' => now()->toDateString(),
    ]);

    $userB = \App\Models\User::factory()->create([
        'company_id' => $companyB->id,
    ]);
    $userB->givePermissionTo('stock.transfer');

    session(['company_id' => $companyB->id]);

    $this->actingAs($userB)
        ->delete(route('stock-transfers.destroy', $transferB))
        ->assertRedirect(route('stock-transfers.index'));

    $sourceStock = ProductStock::withoutCompanyScope()
        ->where('company_id', $companyB->id)
        ->where('product_id', $productB->id)
        ->where('warehouse_id', $sourceB->id)
        ->first();

    $destinationStock = ProductStock::withoutCompanyScope()
        ->where('company_id', $companyB->id)
        ->where('product_id', $productB->id)
        ->where('warehouse_id', $destinationB->id)
        ->first();

    expect((float) $sourceStock->quantity)->toBe(15.0)
        ->and((float) $destinationStock->quantity)->toBe(0.0)
        ->and(
            StockTransfer::withoutCompanyScope()->find($transferB->id)
        )->toBeNull();
});
