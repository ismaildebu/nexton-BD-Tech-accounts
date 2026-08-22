<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData($companyId)
    {
        /*
        |--------------------------------------------------------------------------
        | Core Account Balances
        |--------------------------------------------------------------------------
        */

        $cashBalance = $this->getAccountBalance($companyId, 'Cash in Hand');
        $bankBalance = $this->getAccountBalance($companyId, 'Bank Account');
        $totalIncome = $this->getAccountTypeBalance($companyId, 'Income');
        $totalExpense = $this->getAccountTypeBalance($companyId, 'Expense');
        $receivable = $this->getAccountBalance($companyId, 'Accounts Receivable');
        $payable = $this->getAccountBalance($companyId, 'Accounts Payable');

        /*
        |--------------------------------------------------------------------------
        | Table Data Queries
        |--------------------------------------------------------------------------
        */

        $recentTransactions = Transaction::where('company_id', $companyId)
            ->latest()
            ->take(10)
            ->get();

        $topAccounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->take(10)
            ->get();

        $recentActivities = Transaction::where('company_id', $companyId)
            ->latest()
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Monthly Income & Expense Chart (Optimized Loop with Single Query)
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $monthlyLedgerData = LedgerEntry::join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('ledger_entries.company_id', $companyId)
            ->whereBetween('ledger_entries.created_at', [$startDate, $endDate])
            ->whereIn('accounts.account_type', ['Income', 'Expense'])
            ->select(
                DB::raw('YEAR(ledger_entries.created_at) as year'),
                DB::raw('MONTH(ledger_entries.created_at) as month'),
                'accounts.account_type',
                DB::raw('SUM(ledger_entries.credit) as total_credit'),
                DB::raw('SUM(ledger_entries.debit) as total_debit')
            )
            ->groupBy('year', 'month', 'accounts.account_type')
            ->get()
            ->groupBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        $months = [];
        $incomeChart = [];
        $expenseChart = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');

            $months[] = $date->format('M');

            $periodData = $monthlyLedgerData->get($key);

            $incomeChart[] = $periodData 
                ? (float) $periodData->where('account_type', 'Income')->sum('total_credit') 
                : 0.0;

            $expenseChart[] = $periodData 
                ? (float) $periodData->where('account_type', 'Expense')->sum('total_debit') 
                : 0.0;
        }

        return [
            'cashBalance'        => $cashBalance,
            'bankBalance'        => $bankBalance,
            'totalIncome'        => $totalIncome,
            'totalExpense'       => $totalExpense,
            'netProfit'          => $totalIncome - $totalExpense,
            'receivable'         => $receivable,
            'payable'            => $payable,
            'companyCount'       => Company::count(),
            'recentTransactions' => $recentTransactions,
            'topAccounts'        => $topAccounts,
            'recentActivities'   => $recentActivities,
            'months'             => $months,
            'incomeChart'        => $incomeChart,
            'expenseChart'       => $expenseChart,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Specific Account Balance
    |--------------------------------------------------------------------------
    */

    private function getAccountBalance($companyId, $accountName)
    {
        $account = Account::where('company_id', $companyId)
            ->where('account_name', $accountName)
            ->first();

        if (!$account) {
            return 0;
        }

        $totals = LedgerEntry::where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        $debit = $totals->total_debit;
        $credit = $totals->total_credit;

        if ($account->balance_type == 'Debit') {
            return $account->opening_balance + ($debit - $credit);
        }

        return $account->opening_balance + ($credit - $debit);
    }

    /*
    |--------------------------------------------------------------------------
    | Account Type Balance
    |--------------------------------------------------------------------------
    */

    private function getAccountTypeBalance($companyId, $type)
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('account_type', $type)
            ->pluck('id');

        if ($accounts->isEmpty()) {
            return 0;
        }

        $totals = LedgerEntry::where('company_id', $companyId)
            ->whereIn('account_id', $accounts)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        $debit = $totals->total_debit;
        $credit = $totals->total_credit;

        if ($type == 'Income') {
            return $credit - $debit;
        }

        return $debit - $credit;
    }
}