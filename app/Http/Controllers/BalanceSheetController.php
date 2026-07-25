<?php

namespace App\Http\Controllers;

use App\Models\Account;

class BalanceSheetController extends Controller
{
    public function index()
    {
        $assets = Account::with('ledgerEntries')
            ->where('account_type', 'Asset')
            ->orderBy('account_code')
            ->get();

        $liabilities = Account::with('ledgerEntries')
            ->where('account_type', 'Liability')
            ->orderBy('account_code')
            ->get();

        $equity = Account::with('ledgerEntries')
            ->where('account_type', 'Equity')
            ->orderBy('account_code')
            ->get();

        $income = Account::with('ledgerEntries')
            ->where('account_type', 'Income')
            ->get();

        $expense = Account::with('ledgerEntries')
            ->where('account_type', 'Expense')
            ->get();

        return view(
            'balance-sheet.index',
            compact(
                'assets',
                'liabilities',
                'equity',
                'income',
                'expense'
            )
        );
    }
}