<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseBillController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    Route::resource('vendors', VendorController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:vendors.view')
        ->middlewareFor(['create', 'store'], 'can-permission:vendors.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:vendors.edit')
        ->middlewareFor('destroy', 'can-permission:vendors.delete');

    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'can-permission:purchase-orders.view')
        ->middlewareFor(['create', 'store'], 'can-permission:purchase-orders.create')
        ->middlewareFor('destroy', 'can-permission:purchase-orders.delete');
    Route::post('purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])
        ->middleware('can-permission:purchase-orders.edit')->name('purchase-orders.status');

    Route::resource('purchase-bills', PurchaseBillController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'can-permission:purchase-bills.view')
        ->middlewareFor(['create', 'store'], 'can-permission:purchase-bills.create')
        ->middlewareFor('destroy', 'can-permission:purchase-bills.delete');
    Route::post('purchase-bills/{purchaseBill}/payment', [PurchaseBillController::class, 'addPayment'])
        ->middleware('can-permission:purchase-bills.edit')->name('purchase-bills.payment');
});