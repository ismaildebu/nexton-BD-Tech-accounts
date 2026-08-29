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
    Route::resource('accounts', AccountController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:accounts.view')
        ->middlewareFor(['create', 'store'], 'can-permission:accounts.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:accounts.edit')
        ->middlewareFor('destroy', 'can-permission:accounts.delete');
    Route::resource('bank-accounts', BankAccountController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:banking.view')
        ->middlewareFor(['create', 'store'], 'can-permission:banking.create')
        ->middlewareFor(['edit', 'update'], 'can-permission:banking.edit')
        ->middlewareFor('destroy', 'can-permission:banking.delete');
    Route::resource('financial-years', FinancialYearController::class)
        ->middlewareFor(['index', 'show'], 'can-permission:financial-years.view')
        ->middlewareFor(['create', 'store'], 'can-permission:financial-years.manage')
        ->middlewareFor(['edit', 'update', 'destroy'], 'can-permission:financial-years.manage');
    Route::resource('voucher-types', VoucherTypeController::class)
        ->middlewareFor('index', 'can-permission:voucher-types.view')
        ->middlewareFor(['create', 'store', 'show', 'edit', 'update', 'destroy'], 'can-permission:voucher-types.manage');

    // Legacy redirects
    Route::get('/transactions', fn() => redirect()->route('vouchers.index'))
        ->middleware('can-permission:vouchers.view')->name('transactions.index');
    Route::get('/transactions/create', fn() => redirect()->route('vouchers.create'))
        ->middleware('can-permission:vouchers.create')->name('transactions.create');
    Route::get('/transactions/{id}', fn($id) => redirect()->route('vouchers.show', $id))
        ->middleware('can-permission:vouchers.view')->name('transactions.show');
    Route::get('/transactions/{id}/edit', fn($id) => redirect()->route('vouchers.edit', $id))
        ->middleware('can-permission:vouchers.edit')->name('transactions.edit');

    Route::get('/journal-vouchers', fn() => redirect()->route('vouchers.index'))
        ->middleware('can-permission:vouchers.view')->name('journal-vouchers.index');
    Route::get('/journal-vouchers/create', fn() => redirect()->route('vouchers.create'))
        ->middleware('can-permission:vouchers.create')->name('journal-vouchers.create');
    Route::post('/journal-vouchers', fn() => redirect()->route('vouchers.create'))
        ->middleware('can-permission:vouchers.create')->name('journal-vouchers.store');

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

            Route::get('/ledger', [LedgerController::class, 'index'])
                ->middleware('can-permission:ledger.view')->name('ledger.index');
            
            
            Route::get('/trial-balance', [TrialBalanceController::class, 'index'])
                 ->middleware('can-permission:trial-balance.view')->name('trial-balance.index');
            Route::get('/trial-balance/print', [TrialBalanceController::class, 'print'])
                ->middleware('can-permission:trial-balance.view')->name('trial-balance.print');
            Route::get('/trial-balance/pdf', [TrialBalanceController::class, 'downloadPdf'])
                ->middleware('can-permission:trial-balance.view')->name('trial-balance.pdf');
                    
            
            Route::get('/profit-loss', [ProfitLossController::class, 'index'])
                ->middleware('can-permission:profit-loss.view')->name('profit-loss.index');
            Route::get('/profit-loss/print', [ProfitLossController::class, 'print'])
                ->middleware('can-permission:profit-loss.view')->name('profit-loss.print');
            Route::get('/profit-loss/pdf', [ProfitLossController::class, 'downloadPdf'])
                ->middleware('can-permission:profit-loss.view')->name('profit-loss.pdf');


            Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
                ->middleware('can-permission:balance-sheet.view')->name('balance-sheet.index');
            Route::get('/balance-sheet/print', [BalanceSheetController::class, 'print'])
                ->middleware('can-permission:balance-sheet.view')->name('balance-sheet.print');
            Route::get('/balance-sheet/pdf', [BalanceSheetController::class, 'pdf'])
                ->middleware('can-permission:balance-sheet.view')->name('balance-sheet.pdf');




            Route::get('/cash-flow', [CashFlowController::class, 'index'])->middleware('can-permission:cash-flow.view')->name('cash-flow.index');

    // Alias for the sidebar link in app.blade.php
    Route::get('/reports/ledger', [LedgerController::class, 'index'])
        ->middleware('can-permission:ledger.view')->name('reports.ledger');
});