<?php

namespace App\Http\Controllers;

use App\Models\Account;

class ProfitLossController extends Controller
{
    public function index()
    {
        $incomeAccounts = Account::with('ledgerEntries')
            ->where('account_type', 'Income')
            ->orderBy('account_code')
            ->get();

        $expenseAccounts = Account::with('ledgerEntries')
            ->where('account_type', 'Expense')
            ->orderBy('account_code')
            ->get();

        return view(
            'profit-loss.index',
            compact(
                'incomeAccounts',
                'expenseAccounts'
            )
        );
    }
}