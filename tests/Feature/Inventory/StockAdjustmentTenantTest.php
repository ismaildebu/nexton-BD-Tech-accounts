<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not allow direct stock adjustment against another company product or warehouse', function () {
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

    $warehouseB = Warehouse::create([
        'company_id' => $companyB->id,
        'name' => 'Company B Warehouse',
        'location' => 'B',
    ]);

    session(['company_id' => $companyA->id]);

    expect(fn () => StockMovement::adjustStock(
        $productB->id,
        $warehouseB->id,
        5,
        'in'
    ))->toThrow(RuntimeException::class);

    expect(
        ProductStock::withoutCompanyScope()
            ->where('product_id', $productB->id)
            ->where('warehouse_id', $warehouseB->id)
            ->exists()
    )->toBeFalse();
});
