<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) session('company_id');

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get();

        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        $selectedAccount = null;
        $ledger          = collect();
        $openingBalance  = 0.0;
        $runningDebit    = 0.0;
        $runningCredit   = 0.0;

        if ($request->filled('account_id')) {

            $selectedAccount = Account::query()
                ->where('company_id', $companyId)
                ->where('id', $request->input('account_id'))
                ->firstOrFail();

            // -------------------------------------------------------
            // Opening Balance
            // opening_balance field অথবা account model accessor থেকে নিন।
            // যদি date filter দেওয়া থাকে, filter-এর আগের entries দিয়ে
            // opening balance হিসাব করতে হবে।
            // -------------------------------------------------------
            $openingBalance = $this->calculateOpeningBalance(
                account:    $selectedAccount,
                companyId:  $companyId,
                fromDate:   $request->input('from_date'),
                financialYearId: $request->input('financial_year_id'),
            );

            // -------------------------------------------------------
            // Main Ledger Query
            // -------------------------------------------------------
            $query = LedgerEntry::query()
                ->with(['transaction.voucherType'])
                ->where('company_id', $companyId)
                ->where('account_id', $request->input('account_id'))
                ->where('is_reversed', false);

            if ($request->filled('financial_year_id')) {
                $query->where('financial_year_id', $request->input('financial_year_id'));
            }

            if ($request->filled('from_date')) {
                $query->whereDate('voucher_date', '>=', $request->input('from_date'));
            }

            if ($request->filled('to_date')) {
                $query->whereDate('voucher_date', '<=', $request->input('to_date'));
            }

            $entries = $query
                ->orderBy('voucher_date')
                ->orderBy('id')
                ->get();

            // -------------------------------------------------------
            // ✅ Fix: Account type অনুযায়ী running balance হিসাব
            //
            // ❌ পুরাতন সমস্যা:
            //    $balance += debit - credit;
            //    → সব account-এ একই formula, Liability/Income account-এ ভুল
            //
            // ✅ নতুন সমাধান:
            //    isDebitNormal() চেক করে sign ঠিক করা হচ্ছে।
            //    Asset/Expense  → Debit বাড়ালে balance বাড়ে (Debit Normal)
            //    Liability/Equity/Income → Credit বাড়ালে balance বাড়ে (Credit Normal)
            // -------------------------------------------------------
            $isDebitNormal = $selectedAccount->isDebitNormal();
            $balance       = $openingBalance;

            $ledger = $entries->map(function ($entry) use (
                &$balance,
                &$runningDebit,
                &$runningCredit,
                $isDebitNormal
            ) {
                $debit  = (float) $entry->debit_amount;
                $credit = (float) $entry->credit_amount;

                $runningDebit  += $debit;
                $runningCredit += $credit;

                if ($isDebitNormal) {
                    // Asset / Expense: Debit বাড়লে balance বাড়ে
                    $balance += $debit - $credit;
                } else {
                    // Liability / Equity / Income: Credit বাড়লে balance বাড়ে
                    $balance += $credit - $debit;
                }

                $entry->running_balance    = $balance;
                $entry->balance_is_debit   = $isDebitNormal ? $balance >= 0 : $balance < 0;

                return $entry;
            });
        }

        return view('ledger.index', compact(
            'accounts',
            'financialYears',
            'ledger',
            'selectedAccount',
            'openingBalance',
            'runningDebit',
            'runningCredit',
        ));
    }

    // ---------------------------------------------------------------
    // Private Helper
    // ---------------------------------------------------------------

    /**
     * Date filter-এর আগের entries দিয়ে opening balance হিসাব করো।
     * কোনো filter না থাকলে account-এর opening_balance ব্যবহার করো।
     */
    private function calculateOpeningBalance(
        Account $account,
        int     $companyId,
        ?string $fromDate,
        ?string $financialYearId,
    ): float {

        // Date filter নেই → account-এর নিজস্ব opening balance
        if (empty($fromDate)) {
            return (float) ($account->opening_balance ?? 0);
        }

        // Date filter আছে → filter-এর আগের সব entry দিয়ে balance বের করো
        $query = LedgerEntry::query()
            ->where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->where('is_reversed', false)
            ->whereDate('voucher_date', '<', $fromDate);

        if (! empty($financialYearId)) {
            $query->where('financial_year_id', $financialYearId);
        }

        $prior = $query->selectRaw('
            COALESCE(SUM(debit_amount), 0)  AS prior_debit,
            COALESCE(SUM(credit_amount), 0) AS prior_credit
        ')->first();

        $priorDebit  = (float) $prior->prior_debit;
        $priorCredit = (float) $prior->prior_credit;
        $opening     = (float) ($account->opening_balance ?? 0);

        if ($account->isDebitNormal()) {
            return $opening + ($priorDebit - $priorCredit);
        }

        return $opening + ($priorCredit - $priorDebit);
    }
}