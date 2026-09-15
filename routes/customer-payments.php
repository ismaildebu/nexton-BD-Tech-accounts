<?php

use App\Http\Controllers\CustomerPaymentController;
use Illuminate\Support\Facades\Route;

// Customer Routes
Route::get('/customer/pay/{customerCode}', [CustomerPaymentController::class, 'show'])
    ->name('customer-payment.show');

Route::post('/customer/pay/{customerCode}', [CustomerPaymentController::class, 'initiatePayment'])
    ->name('customer-payment.initiate');

Route::get('/customer/pay/bkash/{payment}', [CustomerPaymentController::class, 'bkashForm'])
    ->name('customer-payment.bkash.form');

Route::post('/customer/pay/bkash/{payment}', [CustomerPaymentController::class, 'submitBkashPayment'])
    ->name('customer-payment.bkash.submit');

Route::get('/customer/payment/success/{payment}', [CustomerPaymentController::class, 'success'])
    ->name('customer-payment.success');

Route::get('/customer/payment/receipt/{payment}', [CustomerPaymentController::class, 'downloadReceipt'])
    ->name('customer-payment.receipt.download');

// Webhooks
Route::post('/webhooks/payment/sslcommerz/ipn', [CustomerPaymentController::class, 'ipnWebhook'])
    ->name('customer-payment.sslcommerz.ipn')
    ->withoutMiddleware('csrf');

Route::get('/customer/payment/sslcommerz/success', [CustomerPaymentController::class, 'sslcommerzSuccess'])
    ->name('customer-payment.sslcommerz.success');

    // ============================================================
// Admin Routes (with admin middleware)
// ============================================================
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/customer-payments', [CustomerPaymentController::class, 'adminList'])
        ->name('customer-payment.admin.list');

    Route::get('/admin/customer-payments/{payment}', [CustomerPaymentController::class, 'adminShow'])
        ->name('customer-payment.admin.show');

    Route::post('/admin/customer-payments/{payment}/verify', [CustomerPaymentController::class, 'verify'])
        ->name('customer-payment.verify');

    Route::post('/admin/customer-payments/{payment}/reject', [CustomerPaymentController::class, 'reject'])
        ->name('customer-payment.reject');
});