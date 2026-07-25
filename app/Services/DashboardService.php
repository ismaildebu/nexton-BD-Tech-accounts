<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData($companyId)
    {

        /*
        |--------------------------------------------------------------------------
        | Cash Balance
        |--------------------------------------------------------------------------
        */

        $cashBalance = $this->getAccountBalance(
            $companyId,
            'Cash in Hand'
        );


        /*
        |--------------------------------------------------------------------------
        | Bank Balance
        |--------------------------------------------------------------------------
        */

        $bankBalance = $this->getAccountBalance(
            $companyId,
            'Bank Account'
        );


        /*
        |--------------------------------------------------------------------------
        | Income
        |--------------------------------------------------------------------------
        */

        $totalIncome = $this->getAccountTypeBalance(
            $companyId,
            'Income'
        );


        /*
        |--------------------------------------------------------------------------
        | Expense
        |--------------------------------------------------------------------------
        */

        $totalExpense = $this->getAccountTypeBalance(
            $companyId,
            'Expense'
        );


        /*
        |--------------------------------------------------------------------------
        | Receivable
        |--------------------------------------------------------------------------
        */

        $receivable = $this->getAccountBalance(
            $companyId,
            'Accounts Receivable'
        );


        /*
        |--------------------------------------------------------------------------
        | Payable
        |--------------------------------------------------------------------------
        */

        $payable = $this->getAccountBalance(
            $companyId,
            'Accounts Payable'
        );


        return [

            'cashBalance'  => $cashBalance,

            'bankBalance'  => $bankBalance,

            'totalIncome'  => $totalIncome,

            'totalExpense' => $totalExpense,

            'netProfit'    => $totalIncome - $totalExpense,

            'receivable'   => $receivable,

            'payable'      => $payable,

            'companyCount' => Company::count(),

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


        $debit = LedgerEntry::where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->sum('debit');


        $credit = LedgerEntry::where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->sum('credit');


        /*
        Asset Account:
        Debit - Credit

        Liability:
        Credit - Debit
        */

        if ($account->balance_type == 'Debit') {

            return $account->opening_balance 
                + ($debit - $credit);

        }


        return $account->opening_balance 
            + ($credit - $debit);

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


        $debit = LedgerEntry::where('company_id', $companyId)
            ->whereIn('account_id', $accounts)
            ->sum('debit');


        $credit = LedgerEntry::where('company_id', $companyId)
            ->whereIn('account_id', $accounts)
            ->sum('credit');


        // Income normally Credit balance
        if ($type == 'Income') {

            return $credit - $debit;

        }


        // Expense normally Debit balance
        return $debit - $credit;

    }

}