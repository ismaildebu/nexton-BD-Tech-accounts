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

    // Legal Documents
        Route::get(
            'legal-documents/{legalDocument}/preview',
            [LegalDocumentController::class, 'preview']
        )->middleware('can-permission:legal-documents.view')->name('legal-documents.preview');

   Route::resource('legal-documents', LegalDocumentController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:legal-documents.view')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'can-permission:legal-documents.manage');
    Route::get('legal-documents/{legalDocument}/download', [LegalDocumentController::class, 'download'])
        ->middleware('can-permission:legal-documents.view')->name('legal-documents.download');
    Route::post('legal-documents/{legalDocument}/mark-as-reviewed', [LegalDocumentController::class, 'markAsReviewed'])
        ->middleware('can-permission:legal-documents.manage')->name('legal-documents.mark-as-reviewed');
    
    Route::prefix('api/legal-documents')->name('api.legal-documents.')->group(function (): void {
        Route::get('/expiring', [LegalDocumentController::class, 'expiringDocuments'])->name('expiring');
        Route::get('/statistics', [LegalDocumentController::class, 'statistics'])->name('statistics');
        
    });
});