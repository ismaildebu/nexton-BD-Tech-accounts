<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BankingController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    // Settings & Reports
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/banking', [BankingController::class, 'index'])->name('banking.index');

    // Legal Documents
        Route::get(
            'legal-documents/{legalDocument}/preview',
            [LegalDocumentController::class, 'preview']
        )->name('legal-documents.preview');

   Route::resource('legal-documents', LegalDocumentController::class);
    Route::get('legal-documents/{legalDocument}/download', [LegalDocumentController::class, 'download'])
        ->name('legal-documents.download');
    Route::post('legal-documents/{legalDocument}/mark-as-reviewed', [LegalDocumentController::class, 'markAsReviewed'])
        ->name('legal-documents.mark-as-reviewed');
    
    Route::prefix('api/legal-documents')->name('api.legal-documents.')->group(function (): void {
        Route::get('/expiring', [LegalDocumentController::class, 'expiringDocuments'])->name('expiring');
        Route::get('/statistics', [LegalDocumentController::class, 'statistics'])->name('statistics');
        
    });
});