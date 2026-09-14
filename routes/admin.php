<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentMonitoringController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        
        // Payment Monitoring
        Route::get('/payments', [PaymentMonitoringController::class, 'index'])
            ->name('payments.index');
        
        Route::get('/payments/{payment}', [PaymentMonitoringController::class, 'show'])
            ->name('payments.show');
        
        Route::get('/users/{user}/payments', [PaymentMonitoringController::class, 'userPayments'])
            ->name('users.payments');
        
        // Payment Verification
        Route::post('/payments/{payment}/verify', [PaymentMonitoringController::class, 'verifyPayment'])
            ->name('payments.verify');
        
        Route::post('/payments/{payment}/reject', [PaymentMonitoringController::class, 'rejectPayment'])
            ->name('payments.reject');
    });