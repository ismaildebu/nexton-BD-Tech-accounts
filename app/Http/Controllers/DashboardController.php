<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionDetail;


class DashboardController extends Controller
{
    /**
     * Display the main accounting dashboard.
     *
     * NOTE: Every query below is written against a plausible schema
     * (invoices, expenses, transactions, bank_accounts). Adjust table /
     * column names to match your actual migrations. Where a model may
     * not exist yet, a safe fallback / placeholder array is provided so
     * the view never breaks during development.
     */
    public function index(Request $request)
    {
        $year = $request->integer('year', now()->year);

        // ---------------------------------------------------------------
        // 1. TOP SUMMARY CARDS
        // ---------------------------------------------------------------

       $totalRevenue = Transaction::query()
            ->where('company_id', session('company_id'))
            ->where('transaction_type', 'Income')
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        $revenueTrend = Transaction::query()
    ->where('company_id', session('company_id'))
    ->where('transaction_type', 'Income')
    ->whereYear('transaction_date', $year)
    ->selectRaw('MONTH(transaction_date) as m, SUM(amount) as total')
    ->groupBy('m')
    ->orderBy('m')
    ->pluck('total', 'm')
    ->toArray();

$revenueTrend = $this->fillTwelveMonths($revenueTrend);

        // Total Expenses + trend for line chart
                $totalExpenses = Transaction::query()
                ->where('company_id', session('company_id'))
                ->where('transaction_type', 'Expense')
                ->whereYear('transaction_date', $year)
                ->sum('amount');

        
                $expenseTrend = Transaction::query()
    ->where('company_id', session('company_id'))
    ->where('transaction_type', 'Expense')
    ->whereYear('transaction_date', $year)
    ->selectRaw('MONTH(transaction_date) as m, SUM(amount) as total')
    ->groupBy('m')
    ->orderBy('m')
    ->pluck('total', 'm')
    ->toArray();

$expenseTrend = $this->fillTwelveMonths($expenseTrend);

        // Net Profit
        $netProfit = $totalRevenue - $totalExpenses;
        $netProfitTrend = array_map(
            fn ($rev, $exp) => $rev - $exp,
            $revenueTrend,
            $expenseTrend
        );

        // Pending Invoices: split into Overdue vs Due for the donut chart
        $pendingOverdueCount = Invoice::query()
    ->where('company_id', session('company_id'))
    ->where('status', 'unpaid')
    ->where('due_date', '<', now())
    ->count();


$pendingDueCount = Invoice::query()
    ->where('company_id', session('company_id'))
    ->where('status', 'unpaid')
    ->where('due_date', '>=', now())
    ->count();

        $pendingDueCount = Invoice::query()
            ->where('status', 'unpaid')
            ->where('due_date', '>=', now())
            ->count();

        $pendingTotalCount = $pendingOverdueCount + $pendingDueCount;

        // ---------------------------------------------------------------
        // 2. MIDDLE SECTION CHARTS
        // ---------------------------------------------------------------

        // Revenue vs Expenses (Jan..Dec) — reuse $revenueTrend / $expenseTrend above
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Top Expense Categories
        // Top Expense Categories
$expenseCategories = TransactionDetail::query()
    ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
    ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id')
    ->where('transactions.company_id', session('company_id'))
    ->where('transactions.transaction_type', 'Expense')
    ->whereYear('transactions.transaction_date', $year)
    ->where('transaction_details.debit', '>', 0)
    ->select(
        'accounts.account_name',
        DB::raw('SUM(transaction_details.debit) as total')
    )
    ->groupBy('accounts.account_name')
    ->orderByDesc('total')
    ->pluck('total', 'accounts.account_name')
    ->toArray();

if (empty($expenseCategories)) {
    $expenseCategories = [
        'No Expense Data' => 0,
    ];
}

        // Cash Flow Trend: Inflows vs Outflows for last 6 months
       $cashFlow = Transaction::query()
    ->where('company_id', session('company_id'))
    ->whereYear('transaction_date', $year)
    ->selectRaw("DATE_FORMAT(transaction_date, '%b') as label")
    ->selectRaw("
        SUM(
            CASE
                WHEN transaction_type = 'Income'
                THEN amount
                ELSE 0
            END
        ) as inflow
    ")
    ->selectRaw("
        SUM(
            CASE
                WHEN transaction_type = 'Expense'
                THEN amount
                ELSE 0
            END
        ) as outflow
    ")
    ->groupBy(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"), 'label')
    ->orderBy(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"))
    ->get();

        $cashFlowLabels  = $cashFlow->pluck('label')->toArray();
        $cashFlowInflow  = $cashFlow->pluck('inflow')->toArray();
        $cashFlowOutflow = $cashFlow->pluck('outflow')->toArray();

        // ---------------------------------------------------------------
        // 3. BOTTOM SECTION TABLES
        // ---------------------------------------------------------------

        // Bank Balance Summary with a mini sparkline history per account
        $bankAccounts = BankAccount::query()
            ->where('company_id', session('company_id'))
            ->where('is_active', true)
            ->select(
                'id',
                'account_name',
                'bank_name',
                'balance'
            )
            ->orderByDesc('balance')
            ->get()
    ->map(function ($account) {
        $account->sparkline = $account->balanceHistory ?? $this->placeholderSparkline();
        return $account;
    });

        // Recent Activity (last 8 transactions across the ledger)
        $recentActivity = Transaction::query()
    ->where('company_id', session('company_id'))
    ->latest('transaction_date')
    ->take(8)
    ->get([
        'id',
        'voucher_no',
        'transaction_type',
        'amount',
        'transaction_date',
        'description',
    ]);

        return view('dashboard.index', compact(
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
            'year'
        ));
    }

    /**
     * Ensure a Jan-Dec (1-12) keyed array with zero-filled gaps.
     */
    private function fillTwelveMonths(array $data): array
    {
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[$m] = $data[$m] ?? 0;
        }
        return array_values($result);
    }

    /**
     * Placeholder sparkline data for a bank account until a real
     * daily-balance-history table/query is wired up.
     */
    private function placeholderSparkline(): array
    {
        return [0, 0, 0, 0, 0, 0, 0];
    }
}
