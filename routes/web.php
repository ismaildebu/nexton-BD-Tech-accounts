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
use App\Http\Controllers\LegalDocumentController;

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\SettingController;





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


// Legal Documents Routes
    Route::prefix('legal-documents')->name('legal-documents.')->group(function () {
        Route::get('/', [LegalDocumentController::class, 'index'])->name('index');
        Route::get('/create', [LegalDocumentController::class, 'create'])->name('create');
        Route::post('/', [LegalDocumentController::class, 'store'])->name('store');
        Route::get('/{legalDocument}', [LegalDocumentController::class, 'show'])->name('show');
        Route::get('/{legalDocument}/edit', [LegalDocumentController::class, 'edit'])->name('edit');
        Route::put('/{legalDocument}', [LegalDocumentController::class, 'update'])->name('update');
        Route::delete('/{legalDocument}', [LegalDocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{legalDocument}/download', [LegalDocumentController::class, 'download'])->name('download');
        Route::post('/{legalDocument}/mark-as-reviewed', [LegalDocumentController::class, 'markAsReviewed'])->name('mark-as-reviewed');
    });

    // API Routes for Legal Documents
    Route::prefix('api/legal-documents')->name('api.legal-documents.')->group(function () {
        Route::get('/expiring', [LegalDocumentController::class, 'expiringDocuments'])->name('expiring');
        Route::get('/statistics', [LegalDocumentController::class, 'statistics'])->name('statistics');
    });



/*

dashboard route 
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard.index');

    Route::get('/invoices', [InvoiceController::class, 'index'])
        ->name('invoices.index');

    Route::get('/expenses', [ExpenseController::class, 'index'])
        ->name('expenses.index');

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/banking', [BankingController::class, 'index'])
        ->name('banking.index');

    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings.index');
});

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

require __DIR__.'/auth.php';