<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalaryController;

// NOTE: original route file had no 'module:hr' middleware here.
// If your app now has an 'hr' module toggle and you WANT employees/salaries
// gated behind it, add 'module:hr' back below. Left out here to restore
// original (pre-split) behavior and avoid an unintended access regression.
Route::middleware(['auth', 'verified', 'company'])
    ->group(function (): void {

        Route::get('/employees', [EmployeeController::class, 'index'])->middleware('can-permission:employees.view')->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->middleware('can-permission:employees.create')->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->middleware('can-permission:employees.create')->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->middleware('can-permission:employees.view')->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('can-permission:employees.edit')->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('can-permission:employees.edit')->name('employees.update');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('can-permission:employees.edit');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('can-permission:employees.delete')->name('employees.destroy');

        Route::get('/salaries', [SalaryController::class, 'index'])->middleware('can-permission:salaries.view')->name('salaries.index');
        Route::get('/salaries/create', [SalaryController::class, 'create'])->middleware('can-permission:salaries.create')->name('salaries.create');
        Route::post('/salaries', [SalaryController::class, 'store'])->middleware('can-permission:salaries.create')->name('salaries.store');
        Route::get('/salaries/{salary}', [SalaryController::class, 'show'])->middleware('can-permission:salaries.view')->name('salaries.show');
        Route::delete('/salaries/{salary}', [SalaryController::class, 'destroy'])->middleware('can-permission:salaries.delete')->name('salaries.destroy');
        Route::post('/salaries/{salary}/mark-paid', [SalaryController::class, 'markPaid'])
            ->middleware('can-permission:salaries.edit')->name('salaries.mark-paid');
    });