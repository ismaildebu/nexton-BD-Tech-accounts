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

        // ── Ledger constraint: company-scoped, non-reversed only ─────────
        $entryConstraint = function ($query) use ($company_id) {
        $query->where('ledger_entries.company_id', $company_id);

        };

        // ── Balance calculator (eager-loaded entries, zero extra queries) ─
        $calculateBalance = static function (Account $account): float {
            $debit   = (float) $account->allLedgerEntries->sum('debit_amount');
            $credit  = (float) $account->allLedgerEntries->sum('credit_amount');
            $opening = (float) ($account->opening_balance ?? 0);

            return $account->isDebitNormal()
                ? $opening + ($debit - $credit)
                : $opening + ($credit - $debit);
        };

        // ── Load all accounts once (2 queries total) ─────────────────────
        $allAccounts = Account::query()
            ->with(['allLedgerEntries' => $entryConstraint])
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
        $totalReceivable  = $assetAccounts->where('nature', 'Customer')->sum($calculateBalance);
        $totalPayable     = $liabilityAccounts->where('nature', 'Supplier')->sum($calculateBalance);

        // ── Revenue ───────────────────────────────────────────────────────
        $incomeAccountIds = $incomeAccounts->pluck('id');

        $totalRevenue = (float) LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $incomeAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->sum('credit_amount');

        $revenueTrend = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $incomeAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->selectRaw('MONTH(entry_date) as m, SUM(credit_amount) as total')
            ->groupBy('m')->orderBy('m')
            ->pluck('total', 'm')->toArray();

        $revenueTrend = $this->fillTwelveMonths($revenueTrend);

        // ── Expenses ──────────────────────────────────────────────────────
        $expenseAccountIds = $expenseAccounts->pluck('id');

        $totalExpenses = (float) LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $expenseAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->sum('debit_amount');

        $expenseTrend = LedgerEntry::where('company_id', $company_id)
            ->whereIn('account_id', $expenseAccountIds)
            ->where('is_reversed', false)
            ->whereYear('entry_date', $year)
            ->selectRaw('MONTH(entry_date) as m, SUM(debit_amount) as total')
            ->groupBy('m')->orderBy('m')
            ->pluck('total', 'm')->toArray();

        $expenseTrend = $this->fillTwelveMonths($expenseTrend);

        // ── Net profit ────────────────────────────────────────────────────
        $netProfit      = $totalRevenue - $totalExpenses;
        $netProfitTrend = array_map(
            fn($r, $e) => $r - $e,
            $revenueTrend,
            $expenseTrend
        );

        // ── Invoices ──────────────────────────────────────────────────────
        $pendingOverdueCount = Invoice::where('company_id', $company_id)
            ->where('status', 'unpaid')->where('due_date', '<', now())->count();

        $pendingDueCount = Invoice::where('company_id', $company_id)
            ->where('status', 'unpaid')->where('due_date', '>=', now())->count();

        $pendingTotalCount = $pendingOverdueCount + $pendingDueCount;

        // ── Months ────────────────────────────────────────────────────────
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        // ── Top expense categories ────────────────────────────────────────
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

        // ── Cash flow labels ──────────────────────────────────────────────
        $cashFlowLabels  = [];
        $cashFlowInflow  = [];
        $cashFlowOutflow = [];

        for ($m = 1; $m <= 12; $m++) {
            $cashFlowLabels[]  = date('M', mktime(0, 0, 0, $m, 1));
            $cashFlowInflow[]  = $revenueTrend[$m - 1] ?? 0;
            $cashFlowOutflow[] = $expenseTrend[$m - 1] ?? 0;
        }

        // ── Cash & Bank (ledger-based) ────────────────────────────────────
        $cashAccounts       = $assetAccounts->where('nature', 'Cash');
        $bankAccountsLedger = $assetAccounts->where('nature', 'Bank');

        $totalCash = $cashAccounts->sum($calculateBalance);
        $totalBank = $bankAccountsLedger->sum($calculateBalance);

        $cashBankDetails = $cashAccounts->merge($bankAccountsLedger)
            ->map(fn(Account $a) => (object)[
                'name'    => $a->account_name,
                'nature'  => $a->nature,
                'balance' => $calculateBalance($a),
            ])
            ->values();

        // ── Bank accounts module ──────────────────────────────────────────
        $bankAccounts = BankAccount::where('company_id', $company_id)
            ->where('is_active', true)
            ->orderByDesc('balance')
            ->get()
            ->map(function ($account) {
                $account->sparkline = $this->placeholderSparkline();
                return $account;
            });

        // ── Recent activity ───────────────────────────────────────────────
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
                'desc'   => $e->description,
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