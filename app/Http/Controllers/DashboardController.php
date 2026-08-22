<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\BankAccount;
use App\Models\LedgerEntry;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year       = $request->integer('year', now()->year);
        $company_id = session('company_id');

        // ---------------------------------------------------------------
// ASSETS, LIABILITIES, EQUITY
// ---------------------------------------------------------------
$assetAccountIds = Account::where('company_id', $company_id)
    ->where('account_type', 'Asset')->pluck('id');

$liabilityAccountIds = Account::where('company_id', $company_id)
    ->where('account_type', 'Liability')->pluck('id');

$equityAccountIds = Account::where('company_id', $company_id)
    ->where('account_type', 'Equity')->pluck('id');

//  (Asset)
$totalAssets = Account::where('company_id', $company_id)
    ->where('account_type', 'Asset')
    ->get()
    ->sum('current_balance');

// (Liability)
$totalLiabilities = Account::where('company_id', $company_id)
    ->where('account_type', 'Liability')
    ->get()
    ->sum('current_balance');

// (Equity)
$totalEquity = Account::where('company_id', $company_id)
    ->where('account_type', 'Equity')
    ->get()
    ->sum('current_balance');

// (Receivable - Customer nature accounts)
$totalReceivable = Account::where('company_id', $company_id)
    ->where('account_type', 'Asset')
    ->where('nature', 'Customer')
    ->get()
    ->sum('current_balance');

//  (Payable - Supplier nature accounts)
$totalPayable = Account::where('company_id', $company_id)
    ->where('account_type', 'Liability')
    ->where('nature', 'Supplier')
    ->get()
    ->sum('current_balance');
        
        
        // ---------------------------------------------------------------
        // 1. REVENUE — Income type accounts থেকে ledger entries
        // ---------------------------------------------------------------
        $incomeAccountIds = Account::where('company_id', $company_id)
            ->where('account_type', 'Income')
            ->pluck('id');

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
        $expenseAccountIds = Account::where('company_id', $company_id)
            ->where('account_type', 'Expense')
            ->pluck('id');

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
// CASH & BANK BALANCE
// ---------------------------------------------------------------
$cashAccounts = Account::where('company_id', $company_id)
    ->where('account_type', 'Asset')
    ->where('nature', 'Cash')
    ->get();

$bankAccountsLedger = Account::where('company_id', $company_id)
    ->where('account_type', 'Asset')
    ->where('nature', 'Bank')
    ->get();

$totalCash = $cashAccounts->sum('current_balance');
$totalBank = $bankAccountsLedger->sum('current_balance');

$cashBankDetails = $cashAccounts->merge($bankAccountsLedger)->map(function($account) {
    return (object)[
        'name'    => $account->account_name,
        'nature'  => $account->nature,
        'balance' => $account->current_balance,
    ];
});


        // ---------------------------------------------------------------
        // 8. BANK ACCOUNTS
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