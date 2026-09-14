<?php

use App\Http\Controllers\Payment\BkashPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'company'])
    ->post('/invoices/{invoice}/bkash/initiate', [BkashPaymentController::class, 'initiate'])
    ->name('bkash.initiate');

Route::get('/bkash/callback', [BkashPaymentController::class, 'callback'])
    ->name('bkash.callback');
