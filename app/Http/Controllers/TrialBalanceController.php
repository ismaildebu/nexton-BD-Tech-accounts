<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return redirect()
                ->route('dashboard.index')
                ->with('error', 'Please select company first.');
        }

        $accounts = Account::where('company_id', $companyId)
            ->with('ledgerEntries')
            ->orderBy('account_code')
            ->get();

        foreach ($accounts as $account) {

            $account->debit_total = $account->ledgerEntries->sum('debit');

            $account->credit_total = $account->ledgerEntries->sum('credit');

            if (in_array($account->account_type, ['Asset', 'Expense'])) {

                $account->balance =
                    $account->opening_balance +
                    $account->debit_total -
                    $account->credit_total;

            } else {

                $account->balance =
                    $account->opening_balance +
                    $account->credit_total -
                    $account->debit_total;
            }
        }

        return view('trial-balance.index', compact('accounts'));
    }
}