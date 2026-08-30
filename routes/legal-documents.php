<?php

declare(strict_types=1);

use App\Http\Controllers\LegalDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {

    Route::resource('legal-documents', LegalDocumentController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:legal-documents.view')
        ->middlewareFor(
            ['create', 'store', 'edit', 'update', 'destroy'],
            'can-permission:legal-documents.manage'
        );

    Route::get(
        'legal-documents/{legalDocument}/download',
        [LegalDocumentController::class, 'download']
    )
        ->middleware('can-permission:legal-documents.view')
        ->name('legal-documents.download');

    Route::get(
        'legal-documents/{legalDocument}/preview',
        [LegalDocumentController::class, 'preview']
    )
        ->middleware('can-permission:legal-documents.view')
        ->name('legal-documents.preview');

    Route::post(
        'legal-documents/{legalDocument}/review',
        [LegalDocumentController::class, 'markAsReviewed']
    )
        ->middleware('can-permission:legal-documents.manage')
        ->name('legal-documents.mark-as-reviewed');

    Route::get(
        'legal-documents-expiring',
        [LegalDocumentController::class, 'expiringDocuments']
    )
        ->middleware('can-permission:legal-documents.view')
        ->name('legal-documents.expiring');

    Route::get(
        'legal-documents-statistics',
        [LegalDocumentController::class, 'statistics']
    )
        ->middleware('can-permission:legal-documents.view')
        ->name('legal-documents.statistics');
});