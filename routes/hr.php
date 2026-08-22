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

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::get('/salaries', [SalaryController::class, 'index'])->name('salaries.index');
        Route::get('/salaries/create', [SalaryController::class, 'create'])->name('salaries.create');
        Route::post('/salaries', [SalaryController::class, 'store'])->name('salaries.store');
        Route::get('/salaries/{salary}', [SalaryController::class, 'show'])->name('salaries.show');
        Route::delete('/salaries/{salary}', [SalaryController::class, 'destroy'])->name('salaries.destroy');
        Route::post('/salaries/{salary}/mark-paid', [SalaryController::class, 'markPaid'])
            ->name('salaries.mark-paid');
    });