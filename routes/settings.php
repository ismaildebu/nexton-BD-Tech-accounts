<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BankingController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    Route::put('/settings', [SettingController::class, 'update'])
        ->middleware('can-permission:settings.manage')->name('settings.update');

    // Settings & Reports
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('can-permission:settings.view')->name('settings.index');
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('can-permission:reports.view')->name('reports.index');
    Route::get('/banking', [BankingController::class, 'index'])
        ->middleware('can-permission:banking.view')->name('banking.index');

    
});