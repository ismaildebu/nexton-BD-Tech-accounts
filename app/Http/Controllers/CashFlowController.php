<?php

namespace App\Http\Controllers;

use App\Models\Account;

class CashFlowController extends Controller
{
    public function index()
    {
        $cashAccounts = Account::with('ledgerEntries')
            ->whereIn('nature', [
                Account::NATURE_CASH,
                Account::NATURE_BANK
            ])
            ->orderBy('account_code')
            ->get();

        $openingBalance = $cashAccounts->sum('opening_balance');

        $operatingIn = 0;
        $operatingOut = 0;
        $investingIn = 0;
        $investingOut = 0;
        $financingIn = 0;
        $financingOut = 0;

        foreach ($cashAccounts as $account) {

            foreach ($account->ledgerEntries as $entry) {

                $transaction = $entry->transaction;

                if (!$transaction) {
                    continue;
                }

                switch ($transaction->transaction_type) {

                    case 'Income':
                        $operatingIn += $entry->debit;
                        break;

                    case 'Expense':
                        $operatingOut += $entry->credit;
                        break;

                    case 'Asset Purchase':
                        $investingOut += $entry->credit;
                        break;

                    case 'Asset Sale':
                        $investingIn += $entry->debit;
                        break;

                    case 'Capital':
                    case 'Loan Received':
                        $financingIn += $entry->debit;
                        break;

                    case 'Loan Payment':
                    case 'Drawings':
                        $financingOut += $entry->credit;
                        break;

                    case 'Bank Transfer':
                        // Ignore internal transfer
                        break;
                }
            }
        }

        $closingBalance =
            $openingBalance
            + $operatingIn
            - $operatingOut
            + $investingIn
            - $investingOut
            + $financingIn
            - $financingOut;

        return view('cash-flow.index', compact(
            'cashAccounts',
            'openingBalance',
            'operatingIn',
            'operatingOut',
            'investingIn',
            'investingOut',
            'financingIn',
            'financingOut',
            'closingBalance'
        ));
    }
}