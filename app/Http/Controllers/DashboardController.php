<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\BankAccount;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year       = $request->integer('year', now()->year);
        $company_id = session('company_id');

        // ---------------------------------------------------------------
        // Ledger Entry Constraint — company + non-reversed entries only.
        // এটাই সেই fix: BalanceSheetController-এর মতো is_reversed = false
        // ফিল্টার এখানেও প্রয়োগ করা হলো, যাতে cancelled ভাউচারের entry
        // dashboard-এর balance-এ ভুলভাবে যোগ না হয়।
        // ---------------------------------------------------------------
        $entryConstraint = function ($query) use ($company_id) {
            $query->where('ledger_entries.company_id', $company_id)
                  ->where('ledger_entries.is_reversed', false);
        };

        // ---------------------------------------------------------------
        // Balance Calculator — Account::getCurrentBalanceAttribute()-এর
        // বদলে এখানে ব্যবহার করা হচ্ছে, কারণ eager-loaded ledgerEntries
        // কালেকশনের উপর কাজ করে, প্রতি account-এ আলাদা query চালায় না।
        // ---------------------------------------------------------------
        $calculateBalance = static function (Account $account): float {
            $debit   = (float) $account->ledgerEntries->sum('debit_amount');
            $credit  = (float) $account->ledgerEntries->sum('credit_amount');
            $opening = (float) ($account->opening_balance ?? 0);

            return $account->isDebitNormal()
                ? $opening + ($debit - $credit)
                : $opening + ($credit - $debit);
        };

        // ---------------------------------------------------------------
        // সব account একবারে লোড, ledgerEntries eager-load সহ।
        // আগে যেখানে ৭ সেট account-এর জন্য আলাদা আলাদা query +
        // প্রতি account-এ ২টা করে accessor query চলত (১০০+ query),
        // এখন মোট মাত্র ২টা query (accounts + ledger entries)।
        // ---------------------------------------------------------------
        $allAccounts = Account::query()
            ->with(['ledgerEntries' => $entryConstraint])
            ->where('company_id', $company_id)
            ->get();

        $assetAccounts     = $allAccounts->where('account_type', 'Asset');
        $liabilityAccounts = $allAccounts->where('account_type', 'Liability');
        $equityAccounts    = $allAccounts->where('account_type', 'Equity');
        $incomeAccounts    = $allAccounts->where('account_type', 'Income');
        $expenseAccounts   = $allAccounts->where('account_type', 'Expense');

        $totalAssets      = $assetAccounts->sum($calculateBalance);
        $totalLiabilities = $liabilityAccounts->sum($calculateBalance);
        $totalEquity      = $equityAccounts->sum($calculateBalance);

        $totalReceivable = $assetAccounts
            ->where('nature', 'Customer')
            ->sum($calculateBalance);

        $totalPayable = $liabilityAccounts
            ->where('nature', 'Supplier')
            ->sum($calculateBalance);

        // ---------------------------------------------------------------
        // 1. REVENUE — Income type accounts থেকে ledger entries
        // ---------------------------------------------------------------
        $incomeAccountIds = $incomeAccounts->pluck('id');

        $totalRevenue = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $incomeAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->sum('credit_amount') ?? 0;

        $revenueTrend = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $incomeAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->selectRaw('MONTH(entry_date) as m, SUM(credit_amount) as total')
            ->groupBy('m')
            ->orderBy('m')
            ->pluck('total', 'm')
            ->toArray();

        $revenueTrend = $this->fillTwelveMonths($revenueTrend);

        // ---------------------------------------------------------------
        // 2. EXPENSES — Expense type accounts থেকে ledger entries
        // ---------------------------------------------------------------
        $expenseAccountIds = $expenseAccounts->pluck('id');

        $totalExpenses = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $expenseAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->sum('debit_amount') ?? 0;

        $expenseTrend = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $expenseAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->selectRaw('MONTH(entry_date) as m, SUM(debit_amount) as total')
            ->groupBy('m')
            ->orderBy('m')
            ->pluck('total', 'm')
            ->toArray();

        $expenseTrend = $this->fillTwelveMonths($expenseTrend);

        // ---------------------------------------------------------------
        // 3. NET PROFIT
        // ---------------------------------------------------------------
        $netProfit      = $totalRevenue - $totalExpenses;
        $netProfitTrend = array_map(
            fn($rev, $exp) => $rev - $exp,
            $revenueTrend,
            $expenseTrend
        );

        // ---------------------------------------------------------------
        // 4. PENDING INVOICES
        // ---------------------------------------------------------------
        $pendingOverdueCount = Invoice::where('company_id', $company_id)
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->count();

        $pendingDueCount = Invoice::where('company_id', $company_id)
            ->where('status', 'unpaid')
            ->where('due_date', '>=', now())
            ->count();

        $pendingTotalCount = $pendingOverdueCount + $pendingDueCount;

        // ---------------------------------------------------------------
        // 5. MONTHS
        // ---------------------------------------------------------------
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        // ---------------------------------------------------------------
        // 6. TOP EXPENSE CATEGORIES — Account নাম অনুযায়ী
        // ---------------------------------------------------------------
        $expenseCategories = LedgerEntry::where('ledger_entries.company_id', $company_id)
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->whereIn('ledger_entries.account_id', $expenseAccountIds)
            ->where('ledger_entries.is_reversed', false)
            ->whereYear('ledger_entries.entry_date', $year)
            ->where('ledger_entries.debit_amount', '>', 0)
            ->selectRaw('accounts.account_name, SUM(ledger_entries.debit_amount) as total')
            ->groupBy('accounts.account_name')
            ->orderByDesc('total')
            ->pluck('total', 'accounts.account_name')
            ->toArray();

        if (empty($expenseCategories)) {
            $expenseCategories = ['No Expense Data' => 0];
        }

        // ---------------------------------------------------------------
        // 7. CASH FLOW TREND
        // ---------------------------------------------------------------
        $cashFlowLabels  = [];
        $cashFlowInflow  = [];
        $cashFlowOutflow = [];

        for ($m = 1; $m <= 12; $m++) {
            $cashFlowLabels[]  = date('M', mktime(0, 0, 0, $m, 1));
            $cashFlowInflow[]  = $revenueTrend[$m - 1] ?? 0;
            $cashFlowOutflow[] = $expenseTrend[$m - 1] ?? 0;
        }

        // ---------------------------------------------------------------
        // CASH & BANK BALANCE (chart-of-accounts side, ledger-based)
        // ---------------------------------------------------------------
        $cashAccounts       = $assetAccounts->where('nature', 'Cash');
        $bankAccountsLedger = $assetAccounts->where('nature', 'Bank');

        $totalCash = $cashAccounts->sum($calculateBalance);
        $totalBank = $bankAccountsLedger->sum($calculateBalance);

        $cashBankDetails = $cashAccounts->merge($bankAccountsLedger)
            ->map(fn(Account $account) => (object)[
                'name'    => $account->account_name,
                'nature'  => $account->nature,
                'balance' => $calculateBalance($account),
            ])
            ->values();

        // ---------------------------------------------------------------
        // 8. BANK ACCOUNTS (BankAccount module — এটা আলাদা টেবিল,
        //    chart-of-accounts ledger-এর সাথে সরাসরি সংযুক্ত নয়)
        // ---------------------------------------------------------------
        $bankAccounts = BankAccount::where('company_id', $company_id)
            ->where('is_active', true)
            ->orderByDesc('balance')
            ->get()
            ->map(function ($account) {
                $account->sparkline = $this->placeholderSparkline();
                return $account;
            });

        // ---------------------------------------------------------------
        // 9. RECENT ACTIVITY — ledger entries থেকে
        // ---------------------------------------------------------------
        $recentActivity = LedgerEntry::where('ledger_entries.company_id', $company_id)
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('ledger_entries.is_reversed', false)
            ->select(
                'ledger_entries.entry_date as date',
                'accounts.account_name as ref',
                'ledger_entries.debit_amount',
                'ledger_entries.credit_amount',
                'ledger_entries.description',
                'ledger_entries.voucher_number',
            )
            ->latest('ledger_entries.entry_date')
            ->take(8)
            ->get()
            ->map(fn($e) => (object)[
                'date'   => $e->date,
                'type'   => $e->debit_amount > 0 ? 'Debit' : 'Credit',
                'amount' => $e->debit_amount > 0 ? $e->debit_amount : $e->credit_amount,
                'ref'    => $e->voucher_number ?? $e->ref,
            ]);

        return view('dashboard.index', compact(
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'totalReceivable',
            'totalPayable',
            'totalRevenue',
            'revenueTrend',
            'totalExpenses',
            'expenseTrend',
            'netProfit',
            'netProfitTrend',
            'pendingOverdueCount',
            'pendingDueCount',
            'pendingTotalCount',
            'months',
            'expenseCategories',
            'cashFlowLabels',
            'cashFlowInflow',
            'cashFlowOutflow',
            'bankAccounts',
            'recentActivity',
            'year',
            'totalCash',
            'totalBank',
            'cashBankDetails',
        ));
    }

    private function fillTwelveMonths(array $data): array
    {
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[$m] = $data[$m] ?? 0;
        }
        return array_values($result);
    }

    private function placeholderSparkline(): array
    {
        return [0, 0, 0, 0, 0, 0, 0];
    }
}