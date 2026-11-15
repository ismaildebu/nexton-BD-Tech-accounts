<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockTransferController;

Route::middleware(['auth', 'verified', 'company', 'module:inventory'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function (): void {

        Route::get('/products', [InventoryController::class, 'products'])
            ->middleware('can-permission:products.view')
            ->name('products');

        Route::get('/products/create', [InventoryController::class, 'createProduct'])
            ->middleware('can-permission:products.create')
            ->name('products.create');

        Route::post('/products', [InventoryController::class, 'storeProduct'])
            ->middleware('can-permission:products.create')
            ->name('products.store');

        Route::get('/products/{product}/edit', [InventoryController::class, 'editProduct'])
            ->middleware('can-permission:products.edit')
            ->name('products.edit');

        Route::put('/products/{product}', [InventoryController::class, 'updateProduct'])
            ->middleware('can-permission:products.edit')
            ->name('products.update');

        Route::delete('/products/{product}', [InventoryController::class, 'destroyProduct'])
            ->middleware('can-permission:products.delete')
            ->name('products.destroy');

        Route::get('/warehouses', [InventoryController::class, 'warehouses'])
            ->middleware('can-permission:warehouses.view')
            ->name('warehouses');

        Route::post('/warehouses', [InventoryController::class, 'storeWarehouse'])
            ->middleware('can-permission:warehouses.manage')
            ->name('warehouses.store');

        Route::delete('/warehouses/{warehouse}', [InventoryController::class, 'destroyWarehouse'])
            ->middleware('can-permission:warehouses.manage')
            ->name('warehouses.destroy');

        Route::get('/stock-in', [InventoryController::class, 'stockIn'])
            ->middleware('can-permission:stock.view')
            ->name('stock-in');

        Route::post('/stock-in', [InventoryController::class, 'storeStockIn'])
            ->middleware('can-permission:stock.move')
            ->name('stock-in.store');

        Route::get('/stock-out', [InventoryController::class, 'stockOut'])
            ->middleware('can-permission:stock.view')
            ->name('stock-out');

        Route::post('/stock-out', [InventoryController::class, 'storeStockOut'])
            ->middleware('can-permission:stock.move')
            ->name('stock-out.store');

        Route::get('/movements', [InventoryController::class, 'movements'])
            ->middleware('can-permission:stock.view')
            ->name('movements');

        Route::get('/stock-report', [InventoryController::class, 'stockReport'])
            ->middleware('can-permission:stock.view')
            ->name('stock-report');
    });

Route::middleware(['auth', 'verified', 'company', 'module:inventory'])
    ->group(function (): void {

        Route::get('stock-transfers', [StockTransferController::class, 'index'])
            ->middleware('can-permission:stock.view')
            ->name('stock-transfers.index');

        Route::get('stock-transfers/create', [StockTransferController::class, 'create'])
            ->middleware('can-permission:stock.transfer')
            ->name('stock-transfers.create');

        Route::post('stock-transfers', [StockTransferController::class, 'store'])
            ->middleware('can-permission:stock.transfer')
            ->name('stock-transfers.store');

        Route::get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])
            ->middleware('can-permission:stock.view')
            ->name('stock-transfers.show');

        Route::delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])
            ->middleware('can-permission:stock.transfer')
            ->name('stock-transfers.destroy');
    });