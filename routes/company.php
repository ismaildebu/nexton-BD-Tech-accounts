<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySwitchController;

Route::middleware(['auth', 'verified'])->group(function (): void {

    Route::get('/companies', [CompanyController::class, 'index'])
        ->middleware('can-permission:companies.view')->name('companies.index');

    Route::get('/companies/create', [CompanyController::class, 'create'])
        ->middleware('can-permission:companies.create')->name('companies.create');

    Route::post('/companies', [CompanyController::class, 'store'])
        ->middleware('can-permission:companies.create')->name('companies.store');

    Route::get('/companies/{company}', [CompanyController::class, 'show'])
        ->middleware('can-permission:companies.view')->name('companies.show');

    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])
        ->middleware('can-permission:companies.edit')->name('companies.edit');

    Route::put('/companies/{company}', [CompanyController::class, 'update'])
        ->middleware('can-permission:companies.edit')->name('companies.update');

    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])
        ->middleware('can-permission:companies.delete')->name('companies.destroy');

    Route::post('/switch-company', [CompanySwitchController::class, 'switch'])
        ->name('switch.company');
});
