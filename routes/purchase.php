<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseBillController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    Route::resource('vendors', VendorController::class);

    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['edit', 'update']);
    Route::post('purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])
        ->name('purchase-orders.status');

    Route::resource('purchase-bills', PurchaseBillController::class)->except(['edit', 'update']);
    Route::post('purchase-bills/{purchaseBill}/payment', [PurchaseBillController::class, 'addPayment'])
        ->name('purchase-bills.payment');
});