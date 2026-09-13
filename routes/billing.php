<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;

Route::middleware(['auth', 'verified'])->prefix('billing')->name('billing.')->group(function (): void {
    
    // Current Subscription & Overview
    Route::get('/subscription', [SubscriptionController::class, 'index'])
        ->name('subscription');

    // Available Plans
    Route::get('/plans', [SubscriptionController::class, 'showPlans'])
        ->name('plans');

    // Upgrade Plan
    Route::post('/plans/{plan}/upgrade', [SubscriptionController::class, 'upgradePlan'])
        ->name('plans.upgrade');

    // Cancel Subscription
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscription.cancel');

    // Payment History
    Route::get('/payments', [SubscriptionController::class, 'paymentHistory'])
        ->name('payments');



        // Payment Callbacks (no auth needed for IPN)
    Route::post('/payment-ipn', [SubscriptionController::class, 'paymentIPN'])
        ->name('payment-ipn')
        ->withoutMiddleware(['auth', 'verified']);

    Route::get('/payment-success', [SubscriptionController::class, 'paymentSuccess'])
        ->middleware(['auth', 'verified'])
        ->name('payment-success');

    Route::get('/payment-failed', [SubscriptionController::class, 'paymentFailed'])
        ->middleware(['auth', 'verified'])
        ->name('payment-failed');

    Route::get('/payment-cancelled', [SubscriptionController::class, 'paymentCancelled'])
        ->middleware(['auth', 'verified'])
        ->name('payment-cancelled');

    // Initiate payment for plan upgrade
    Route::post('/plans/{plan}/initiate-payment', [SubscriptionController::class, 'initiatePayment'])
        ->middleware(['auth', 'verified'])
        ->name('plans.initiate-payment');
});