<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\FinancialYearController;
use App\Http\Controllers\VoucherTypeController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\CashFlowController;

Route::middleware(['auth', 'verified', 'company'])->group(function (): void {
    
    // Chart of Accounts
    Route::resource('accounts', AccountController::class);
    Route::resource('bank-accounts', BankAccountController::class);
    Route::resource('financial-years', FinancialYearController::class);
    Route::resource('voucher-types', VoucherTypeController::class);

    // Legacy redirects
    Route::get('/transactions', fn() => redirect()->route('vouchers.index'))->name('transactions.index');
    Route::get('/transactions/create', fn() => redirect()->route('vouchers.create'))->name('transactions.create');
    Route::get('/transactions/{id}', fn($id) => redirect()->route('vouchers.show', $id))->name('transactions.show');
    Route::get('/transactions/{id}/edit', fn($id) => redirect()->route('vouchers.edit', $id))->name('transactions.edit');

    Route::get('/journal-vouchers', fn() => redirect()->route('vouchers.index'))->name('journal-vouchers.index');
    Route::get('/journal-vouchers/create', fn() => redirect()->route('vouchers.create'))->name('journal-vouchers.create');
    Route::post('/journal-vouchers', fn() => redirect()->route('vouchers.create'))->name('journal-vouchers.store');

    // Vouchers
    Route::prefix('vouchers')->name('vouchers.')->group(function (): void {
        Route::get('/', [VoucherController::class, 'index'])
            ->middleware('can-permission:vouchers.view')->name('index');
        
        Route::get('/create', [VoucherController::class, 'create'])
            ->middleware('can-permission:vouchers.create')->name('create');
        
        Route::post('/', [VoucherController::class, 'store'])
            ->middleware('can-permission:vouchers.create')->name('store');
        
        Route::get('/{transaction}', [VoucherController::class, 'show'])
            ->middleware('can-permission:vouchers.view')->name('show');
        
        Route::get('/{transaction}/edit', [VoucherController::class, 'edit'])
            ->middleware('can-permission:vouchers.edit')->name('edit');
        
        Route::put('/{transaction}', [VoucherController::class, 'update'])
            ->middleware('can-permission:vouchers.edit')->name('update');
        
        Route::post('/{transaction}/submit', [VoucherController::class, 'submit'])
            ->middleware('can-permission:vouchers.create')->name('submit');
        
        Route::post('/{transaction}/approve', [VoucherController::class, 'approve'])
            ->middleware('can-permission:vouchers.approve')->name('approve');
        
        Route::post('/{transaction}/post', [VoucherController::class, 'post'])
            ->middleware('can-permission:vouchers.post')->name('post');
        
        Route::post('/{transaction}/cancel', [VoucherController::class, 'cancel'])
            ->middleware('can-permission:vouchers.cancel')->name('cancel');
        
        Route::delete('/{transaction}', [VoucherController::class, 'destroy'])
            ->middleware('can-permission:vouchers.delete')->name('destroy');
        
        Route::get('/{transaction}/print', [VoucherController::class, 'print'])
            ->middleware('can-permission:vouchers.print')->name('print');

        Route::get('/{transaction}/pdf', [VoucherController::class, 'downloadPdf'])
            ->middleware('can-permission:vouchers.print')->name('pdf');
    });

            Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
            
            
            Route::get('/trial-balance', [TrialBalanceController::class, 'index'])
                 ->name('trial-balance.index');
            Route::get('/trial-balance/print', [TrialBalanceController::class, 'print'])
                ->name('trial-balance.print');
            Route::get('/trial-balance/pdf', [TrialBalanceController::class, 'downloadPdf'])
                ->name('trial-balance.pdf');
                    
            
            Route::get('/profit-loss', [ProfitLossController::class, 'index'])
                ->name('profit-loss.index');
            Route::get('/profit-loss/print', [ProfitLossController::class, 'print'])
                ->name('profit-loss.print');
            Route::get('/profit-loss/pdf', [ProfitLossController::class, 'downloadPdf'])
                ->name('profit-loss.pdf');


            Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
                ->name('balance-sheet.index');
            Route::get('/balance-sheet/print', [BalanceSheetController::class, 'print'])
                ->name('balance-sheet.print');
            Route::get('/balance-sheet/pdf', [BalanceSheetController::class, 'pdf'])
                ->name('balance-sheet.pdf');




            Route::get('/cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');

    // Alias for the sidebar link in app.blade.php
    Route::get('/reports/ledger', [LedgerController::class, 'index'])->name('reports.ledger');
});