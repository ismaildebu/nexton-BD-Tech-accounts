<?php

declare(strict_types=1);

use App\Http\Controllers\Media\MediaCollectionController;
use App\Http\Controllers\Media\MediaDistributionController;
use App\Http\Controllers\Media\MediaPartyController;
use App\Http\Controllers\Media\MediaReportController;
use App\Http\Controllers\Media\MediaReturnController;
use App\Http\Controllers\Media\PrintOrderController;
use App\Http\Controllers\Media\PrintPlanController;
use App\Http\Controllers\Media\PublicationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'company', 'module:media', 'plan-feature:media'])
    ->prefix('media')
    ->name('media.')
    ->group(function (): void {

        Route::resource('publications', PublicationController::class)
            ->parameters(['publications' => 'publication'])
            ->middleware([
                'index'   => 'can-permission:media-publications.view',
                'show'    => 'can-permission:media-publications.view',
                'create'  => 'can-permission:media-publications.create',
                'store'   => 'can-permission:media-publications.create',
                'edit'    => 'can-permission:media-publications.edit',
                'update'  => 'can-permission:media-publications.edit',
                'destroy' => 'can-permission:media-publications.delete',
            ]);

        // ──────────────────────────────────────────────────────────────
        // IMPORTANT: Bulk routes must come BEFORE Route::resource so
        // Laravel does not treat 'bulk-create' as a {media_party} slug.
        // ──────────────────────────────────────────────────────────────

        // Agent + Hawker bulk entry
        Route::get('parties/bulk-create', [MediaPartyController::class, 'bulkCreate'])
            ->name('parties.bulk-create')
            ->middleware('can-permission:media-parties.create');

        Route::post('parties/bulk-store', [MediaPartyController::class, 'bulkStore'])
            ->name('parties.bulk-store')
            ->middleware('can-permission:media-parties.create');

        // Journalist bulk entry
        Route::get('parties/journalists/bulk-create', [MediaPartyController::class, 'journalistBulkCreate'])
            ->name('parties.journalists.bulk-create')
            ->middleware('can-permission:media-parties.create');

        Route::post('parties/journalists/bulk-store', [MediaPartyController::class, 'journalistBulkStore'])
            ->name('parties.journalists.bulk-store')
            ->middleware('can-permission:media-parties.create');

        // Agent + Hawker share this one resource; `type` distinguishes them.
        Route::resource('parties', MediaPartyController::class)
            ->parameters(['parties' => 'media_party'])
            ->middleware([
                'index'   => 'can-permission:media-parties.view',
                'show'    => 'can-permission:media-parties.view',
                'create'  => 'can-permission:media-parties.create',
                'store'   => 'can-permission:media-parties.create',
                'edit'    => 'can-permission:media-parties.edit',
                'update'  => 'can-permission:media-parties.edit',
                'destroy' => 'can-permission:media-parties.delete',
            ]);

        Route::resource('print-plans', PrintPlanController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware([
                'index'  => 'can-permission:media-print-planning.view',
                'show'   => 'can-permission:media-print-planning.view',
                'create' => 'can-permission:media-print-planning.create',
                'store'  => 'can-permission:media-print-planning.create',
            ]);

        Route::post('print-plans/{print_plan}/approve', [PrintPlanController::class, 'approve'])
            ->name('print-plans.approve')
            ->middleware('can-permission:media-print-planning.approve');

        Route::post('print-plans/{print_plan}/reject', [PrintPlanController::class, 'reject'])
            ->name('print-plans.reject')
            ->middleware('can-permission:media-print-planning.approve');

        Route::resource('print-orders', PrintOrderController::class)
            ->except(['destroy'])
            ->middleware([
                'index'  => 'can-permission:media-print-orders.view',
                'show'   => 'can-permission:media-print-orders.view',
                'create' => 'can-permission:media-print-orders.create',
                'store'  => 'can-permission:media-print-orders.create',
                'edit'   => 'can-permission:media-print-orders.edit',
                'update' => 'can-permission:media-print-orders.edit',
            ]);

        Route::post('print-plans/{print_plan}/print-order', [PrintOrderController::class, 'storeFromPlan'])
            ->name('print-orders.store-from-plan')
            ->middleware('can-permission:media-print-orders.create');

        Route::post('print-orders/{print_order}/approve', [PrintOrderController::class, 'approve'])
            ->name('print-orders.approve')
            ->middleware('can-permission:media-print-orders.approve');

        Route::patch('print-orders/{print_order}/status', [PrintOrderController::class, 'updateStatus'])
            ->name('print-orders.update-status')
            ->middleware('can-permission:media-print-orders.approve');

        Route::get('print-orders/{print_order}/pdf', [PrintOrderController::class, 'downloadPdf'])
            ->name('print-orders.pdf')
            ->middleware('can-permission:media-print-orders.print');

        Route::resource('distributions', MediaDistributionController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware([
                'index'  => 'can-permission:media-distributions.view',
                'show'   => 'can-permission:media-distributions.view',
                'create' => 'can-permission:media-distributions.create',
                'store'  => 'can-permission:media-distributions.create',
            ]);

        Route::post('distributions/{distribution}/confirm', [MediaDistributionController::class, 'confirm'])
            ->name('distributions.confirm')
            ->middleware('can-permission:media-distributions.create');

        Route::get('distributions/{distribution}/dispatch-sheet', [MediaDistributionController::class, 'dispatchSheetPdf'])
            ->name('distributions.dispatch-sheet')
            ->middleware('can-permission:media-distributions.print');

        Route::get('distributions/{distribution}/bundle-slips', [MediaDistributionController::class, 'bundleSlipsPdf'])
            ->name('distributions.bundle-slips')
            ->middleware('can-permission:media-distributions.print');

        Route::get('distributions/{distribution}/bundle-slips/{item}', [MediaDistributionController::class, 'bundleSlipPdf'])
            ->name('distributions.bundle-slip')
            ->middleware('can-permission:media-distributions.print');

        Route::resource('returns', MediaReturnController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware([
                'index'  => 'can-permission:media-returns.view',
                'show'   => 'can-permission:media-returns.view',
                'create' => 'can-permission:media-returns.create',
                'store'  => 'can-permission:media-returns.create',
            ]);

        Route::resource('collections', MediaCollectionController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware([
                'index'  => 'can-permission:media-collections.view',
                'show'   => 'can-permission:media-collections.view',
                'create' => 'can-permission:media-collections.create',
                'store'  => 'can-permission:media-collections.create',
            ]);

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('stock', [MediaReportController::class, 'stockReport'])
                ->name('stock')->middleware('can-permission:media-reports.view');
            Route::get('stock/pdf', [MediaReportController::class, 'stockReportPdf'])
                ->name('stock.pdf')->middleware('can-permission:media-reports.view');
            Route::get('distribution-summary', [MediaReportController::class, 'distributionSummary'])
                ->name('distribution-summary')->middleware('can-permission:media-reports.view');
            Route::get('distribution-summary/pdf', [MediaReportController::class, 'distributionSummaryPdf'])
                ->name('distribution-summary.pdf')->middleware('can-permission:media-reports.view');
            Route::get('return-summary', [MediaReportController::class, 'returnSummary'])
                ->name('return-summary')->middleware('can-permission:media-reports.view');
            Route::get('collection-summary', [MediaReportController::class, 'collectionSummary'])
                ->name('collection-summary')->middleware('can-permission:media-reports.view');
            Route::get('party-ledger', [MediaReportController::class, 'partyLedger'])
                ->name('party-ledger')->middleware('can-permission:media-reports.view');
            Route::get('party-ledger/pdf', [MediaReportController::class, 'partyLedgerPdf'])
                ->name('party-ledger.pdf')->middleware('can-permission:media-reports.view');
        });
    });
