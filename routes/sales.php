<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:customers.view')
        ->middlewareFor(['create', 'store'], 'can-permission:customers.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:customers.edit')
        ->middlewareFor('destroy', 'can-permission:customers.delete');
    
    Route::middleware(['module:sales-orders'])->group(function (): void {
        Route::resource('sales-orders', SalesOrderController::class)->except(['edit', 'update'])
            ->middlewareFor(['index', 'show'], 'can-permission:sales-orders.view')
            ->middlewareFor(['create', 'store'], 'can-permission:sales-orders.create')
            ->middlewareFor('destroy', 'can-permission:sales-orders.delete');
        Route::post('sales-orders/{salesOrder}/status', [SalesOrderController::class, 'updateStatus'])
            ->middleware('can-permission:sales-orders.edit')->name('sales-orders.status');
    });

    Route::resource('invoices', InvoiceController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:invoices.view')
        ->middlewareFor(['create', 'store'], 'can-permission:invoices.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:invoices.edit')
        ->middlewareFor('destroy', 'can-permission:invoices.delete');
    Route::resource('expenses', ExpenseController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:expenses.view')
        ->middlewareFor(['create', 'store'], 'can-permission:expenses.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:expenses.edit')
        ->middlewareFor('destroy', 'can-permission:expenses.delete');
});