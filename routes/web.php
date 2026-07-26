<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\FinancialYearController;
use App\Http\Controllers\VoucherTypeController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\JournalVoucherController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Company Management
    |--------------------------------------------------------------------------
    */
    Route::resource('companies', CompanyController::class);



    /*
|-------------------------------------------------------------------------- 
| Company Switch
|-------------------------------------------------------------------------- 
*/

Route::post('/switch-company', [CompanySwitchController::class, 'switch'])
    ->name('switch.company');

    /*
    |--------------------------------------------------------------------------
    | Chart of Accounts
    |--------------------------------------------------------------------------
    */
    Route::resource('accounts', AccountController::class);
    Route::resource('financial-years', FinancialYearController::class);
    Route::resource('voucher-types', VoucherTypeController::class);

    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */
    Route::resource('transactions', TransactionController::class);

    /*
    |--------------------------------------------------------------------------
    | User Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

        Route::get('/ledger', [LedgerController::class, 'index'])
    ->name('ledger.index');

    Route::get('/trial-balance', [TrialBalanceController::class, 'index'])
    ->name('trial-balance.index');

    Route::get('/profit-loss', [ProfitLossController::class, 'index'])
    ->name('profit-loss.index');

    Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
    ->name('balance-sheet.index');

    Route::middleware(['auth'])->group(function () {

    Route::get('/cash-flow', [CashFlowController::class, 'index'])
        ->name('cash-flow.index');


        Route::prefix('journal-vouchers')->group(function () {

    Route::get(
        '/',
        [JournalVoucherController::class, 'index']
    )->name('journal-vouchers.index');


    Route::get(
        '/create',
        [JournalVoucherController::class, 'create']
    )->name('journal-vouchers.create');


    Route::post(
        '/',
        [JournalVoucherController::class, 'store']
    )->name('journal-vouchers.store');

});

});
});

require __DIR__.'/auth.php';