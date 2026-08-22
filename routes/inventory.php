<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockTransferController;

Route::middleware(['auth', 'verified', 'company', 'module:inventory'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function (): void {
        
        Route::get('/products', [InventoryController::class, 'products'])->name('products');
        Route::get('/products/create', [InventoryController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [InventoryController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{product}/edit', [InventoryController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{product}', [InventoryController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [InventoryController::class, 'destroyProduct'])->name('products.destroy');

        Route::get('/warehouses', [InventoryController::class, 'warehouses'])->name('warehouses');
        Route::post('/warehouses', [InventoryController::class, 'storeWarehouse'])->name('warehouses.store');
        Route::delete('/warehouses/{warehouse}', [InventoryController::class, 'destroyWarehouse'])->name('warehouses.destroy');

        Route::get('/stock-in', [InventoryController::class, 'stockIn'])->name('stock-in');
        Route::post('/stock-in', [InventoryController::class, 'storeStockIn'])->name('stock-in.store');

        Route::get('/stock-out', [InventoryController::class, 'stockOut'])->name('stock-out');
        Route::post('/stock-out', [InventoryController::class, 'storeStockOut'])->name('stock-out.store');

        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
        Route::get('/stock-report', [InventoryController::class, 'stockReport'])->name('stock-report');
    });

Route::middleware(['auth', 'verified', 'company', 'module:inventory'])
    ->group(function (): void {
        Route::resource('stock-transfers', StockTransferController::class)->except(['edit', 'update']);
    });