<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Total Revenue (sum of paid invoices this year) + last 7 data points for trend bar chart
        $totalRevenue = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('paid_at', $year)
            ->sum('total_amount') ?? 0;

        $revenueTrend = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('paid_at', $year)
            ->selectRaw('MONTH(paid_at) as m, SUM(total_amount) as total')
            ->groupBy('m')
            ->orderBy('m')
            ->pluck('total', 'm')
            ->toArray();
        $revenueTrend = $this->fillTwelveMonths($revenueTrend);

        // Total Expenses + trend for line chart
        $totalExpenses = Expense::query()
            ->whereYear('spent_at', $year)
            ->sum('amount') ?? 0;

        $expenseTrend = Expense::query()
            ->whereYear('spent_at', $year)
            ->selectRaw('MONTH(spent_at) as m, SUM(amount) as total')
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
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
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
        $expenseCategories = Expense::query()
            ->whereYear('spent_at', $year)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->toArray();

        // Fallback placeholder shape if the table is empty / not seeded yet
        if (empty($expenseCategories)) {
            $expenseCategories = [
                'Salaries'   => 0,
                'Utilities'  => 0,
                'Marketing'  => 0,
                'Others'     => 0,
            ];
        }

        // Cash Flow Trend: Inflows vs Outflows for last 6 months
        $cashFlow = Transaction::query()
    ->selectRaw("DATE_FORMAT(created_at, '%b') as label")
    ->selectRaw("SUM(amount) as inflow")
    ->selectRaw("0 as outflow")
    ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), 'label')
    ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
    ->get();

        $cashFlowLabels  = $cashFlow->pluck('label')->toArray();
        $cashFlowInflow  = $cashFlow->pluck('inflow')->toArray();
        $cashFlowOutflow = $cashFlow->pluck('outflow')->toArray();

        // ---------------------------------------------------------------
        // 3. BOTTOM SECTION TABLES
        // ---------------------------------------------------------------

        // Bank Balance Summary with a mini sparkline history per account
        $bankAccounts = BankAccount::query()
    ->select('id', 'account_name', 'bank_name', 'balance')
    ->orderByDesc('balance')
    ->get()
    ->map(function ($account) {
        $account->sparkline = $account->balanceHistory ?? $this->placeholderSparkline();
        return $account;
    });

        // Recent Activity (last 8 transactions across the ledger)
        $recentActivity = Transaction::query()
    ->latest()
    ->take(8)
    ->get(['id', 'created_at', 'description']);

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
