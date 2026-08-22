<?php

declare(strict_types=1);

use App\Http\Controllers\Media\MediaCollectionController;
use App\Http\Controllers\Media\MediaDistributionController;
use App\Http\Controllers\Media\MediaPartyController;
use App\Http\Controllers\Media\MediaReturnController;
use App\Http\Controllers\Media\PrintOrderController;
use App\Http\Controllers\Media\PrintPlanController;
use App\Http\Controllers\Media\PublicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Media Business Module Routes
|--------------------------------------------------------------------------
|
| Same conventions as routes/purchase.php and routes/inventory.php:
|   - 'auth' + 'verified' + 'company' -> session-based company isolation
|     (EnsureCompanySelected locks a company-scoped user to their own
|     company_id automatically).
|   - 'module:media' -> only companies with business_type = 'Media' can
|     reach these routes (Company::hasModule()).
|   - 'can-permission:<name>' on each action, following the
|     RoleAndPermissionSeeder group naming used everywhere else.
|
*/

Route::middleware(['auth', 'verified', 'company', 'module:media'])
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

        // Agent + Hawker share this one resource; `type` distinguishes them.
        // There is intentionally no nested/child route implying a
        // parent-child relationship between the two.
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

        // Create a Print Order directly from an Approved Print Plan —
        // ordered_quantity comes from the plan, never hand-entered.
        // (Uses the same create() form — see approvedPlans in the view.)
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
    });
