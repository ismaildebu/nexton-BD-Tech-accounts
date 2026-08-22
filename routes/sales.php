<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    Route::resource('customers', CustomerController::class);
    
    Route::middleware(['module:sales-orders'])->group(function (): void {
        Route::resource('sales-orders', SalesOrderController::class)->except(['edit', 'update']);
        Route::post('sales-orders/{salesOrder}/status', [SalesOrderController::class, 'updateStatus'])
            ->name('sales-orders.status');
    });

    Route::resource('invoices', InvoiceController::class);
    Route::resource('expenses', ExpenseController::class);
});