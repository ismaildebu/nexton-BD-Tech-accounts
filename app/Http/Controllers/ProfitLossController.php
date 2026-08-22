<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProfitLossController extends Controller
{
    /**
     * Display the Profit & Loss Statement.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('profit-loss.index', $context);
    }

    /**
     * Download the Profit & Loss Statement as PDF.
     */
    public function downloadPdf(Request $request): Response
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $pdf = Pdf::loadView('profit-loss.pdf', $context);

        $pdf->setPaper('A4', 'portrait');

        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $companySlug = str($context['company']->company_name)
            ->slug('-')
            ->toString();

        $yearName = str($context['financialYear']->year_name)
            ->slug('-')
            ->toString();

        $periodSlug = str($context['periodKey'])
            ->slug('-')
            ->toString();

        $filename = "profit-loss-{$companySlug}-{$yearName}-{$periodSlug}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Print-friendly Profit & Loss Statement.
     */
    public function print(Request $request): View|RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $context['autoPrint'] = true;

        return view('profit-loss.index', $context);
    }

    /**
     * Resolve company, financial year and report data.
     *
     * Supported report periods:
     *
     * full    = Full Financial Year
     * monthly = One calendar month inside the financial year
     * custom  = Custom date range inside the financial year
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function resolveContext(Request $request): array|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Please login first.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Resolve Company
        |--------------------------------------------------------------------------
        */

        $companyId = session('company_id');

        if (! $companyId && $user->company_id !== null) {
            $companyId = (int) $user->company_id;

            session([
                'company_id' => $companyId,
            ]);
        }

        if (! $companyId) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'Please select a company first.');
        }

        /*
         * Company-scoped users cannot access another company.
         */
        if (
            $user->company_id !== null
            && (int) $user->company_id !== (int) $companyId
        ) {
            abort(403, 'You do not have access to this company.');
        }

        $company = Company::query()
            ->whereKey($companyId)
            ->first();

        if (! $company) {
            session()->forget([
                'company_id',
                'company_name',
                'financial_year_id',
            ]);

            return redirect()
                ->route('companies.index')
                ->with('error', 'Selected company could not be found.');
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Resolve Financial Year
        |--------------------------------------------------------------------------
        */

        $financialYearId = session('financial_year_id');

        $financialYear = null;

        /*
         * First use the financial year stored in session.
         */
        if ($financialYearId) {
            $financialYear = FinancialYear::query()
                ->where('company_id', $company->id)
                ->whereKey($financialYearId)
                ->where('is_active', true)
                ->where('is_closed', false)
                ->first();
        }

        /*
         * Otherwise use the latest active/open financial year.
         */
        if (! $financialYear) {
            $financialYear = FinancialYear::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('is_closed', false)
                ->orderByDesc('start_date')
                ->first();
        }

        if (! $financialYear) {
            return redirect()
                ->route('financial-years.index')
                ->with(
                    'error',
                    'Please select or create an active financial year first.'
                );
        }

        /*
         * Keep company and financial year synchronized.
         */
        session([
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'financial_year_id' => $financialYear->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Resolve Report Period
        |--------------------------------------------------------------------------
        */

        $periodType = (string) $request->query('period', 'full');

        if (! in_array($periodType, ['full', 'monthly', 'custom'], true)) {
            $periodType = 'full';
        }

        $financialYearStart = Carbon::parse(
            $financialYear->start_date
        )->startOfDay();

        $financialYearEnd = Carbon::parse(
            $financialYear->end_date
        )->endOfDay();

        $fromDate = $financialYearStart->copy();
        $toDate = $financialYearEnd->copy();

        $selectedMonth = null;
        $customFrom = null;
        $customTo = null;

        /*
        |--------------------------------------------------------------------------
        | Monthly Report
        |--------------------------------------------------------------------------
        */

        if ($periodType === 'monthly') {
            $month = (string) $request->query('month', '');

            /*
             * Expected format:
             *
             * YYYY-MM
             *
             * Example:
             * 2026-08
             */
            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'Please select a valid month.'
                    );
            }

            try {
                $monthStart = Carbon::createFromFormat(
                    '!Y-m',
                    $month
                )->startOfMonth();

                $monthEnd = $monthStart->copy()->endOfMonth();
            } catch (\Throwable) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'Please select a valid month.'
                    );
            }

            /*
             * The selected month must be completely inside
             * the selected financial year.
             */
            if (
                $monthStart->lt($financialYearStart)
                || $monthEnd->gt($financialYearEnd)
            ) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'The selected month is outside the active financial year.'
                    );
            }

            $fromDate = $monthStart->copy();
            $toDate = $monthEnd->copy();

            $selectedMonth = $monthStart->format('Y-m');
        }

        /*
        |--------------------------------------------------------------------------
        | Custom Date Range
        |--------------------------------------------------------------------------
        */

        if ($periodType === 'custom') {
            $fromInput = (string) $request->query('from', '');
            $toInput = (string) $request->query('to', '');

            try {
                $customFromDate = Carbon::createFromFormat(
                    '!Y-m-d',
                    $fromInput
                )->startOfDay();

                $customToDate = Carbon::createFromFormat(
                    '!Y-m-d',
                    $toInput
                )->endOfDay();
            } catch (\Throwable) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'Please provide a valid custom date range.'
                    );
            }

            if ($customFromDate->gt($customToDate)) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'The start date cannot be later than the end date.'
                    );
            }

            /*
             * Custom range must remain inside the financial year.
             */
            if (
                $customFromDate->lt($financialYearStart)
                || $customToDate->gt($financialYearEnd)
            ) {
                return redirect()
                    ->route('profit-loss.index')
                    ->with(
                        'error',
                        'The custom date range must be inside the active financial year.'
                    );
            }

            $fromDate = $customFromDate->copy();
            $toDate = $customToDate->copy();

            $customFrom = $customFromDate->format('Y-m-d');
            $customTo = $customToDate->format('Y-m-d');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Generate Period Text
        |--------------------------------------------------------------------------
        */

        if ($periodType === 'full') {
            $periodText = sprintf(
                'For the year ended %s',
                $financialYearEnd->format('d F Y')
            );

            $periodKey = 'full-year';
        } elseif ($periodType === 'monthly') {
            $periodText = sprintf(
                'For the month ended %s',
                $toDate->format('d F Y')
            );

            $periodKey = $selectedMonth ?? $toDate->format('Y-m');
        } else {
            $periodText = sprintf(
                'For the period %s to %s',
                $fromDate->format('d F Y'),
                $toDate->format('d F Y')
            );

            $periodKey = sprintf(
                '%s-to-%s',
                $fromDate->format('Y-m-d'),
                $toDate->format('Y-m-d')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Load Income Accounts
        |--------------------------------------------------------------------------
        */

        $incomeAccounts = $this->getReportAccounts(
            companyId: (int) $company->id,
            financialYearId: (int) $financialYear->id,
            accountType: Account::TYPE_INCOME,
            normalBalance: 'credit',
            fromDate: $fromDate,
            toDate: $toDate
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Load Expense Accounts
        |--------------------------------------------------------------------------
        */

        $expenseAccounts = $this->getReportAccounts(
            companyId: (int) $company->id,
            financialYearId: (int) $financialYear->id,
            accountType: Account::TYPE_EXPENSE,
            normalBalance: 'debit',
            fromDate: $fromDate,
            toDate: $toDate
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Totals
        |--------------------------------------------------------------------------
        */

        $totalIncome = round(
            (float) $incomeAccounts->sum('report_amount'),
            2
        );

        $totalExpense = round(
            (float) $expenseAccounts->sum('report_amount'),
            2
        );

        $netResult = round(
            $totalIncome - $totalExpense,
            2
        );

        $netProfit = $netResult > 0
            ? $netResult
            : 0.00;

        $netLoss = $netResult < 0
            ? abs($netResult)
            : 0.00;

        /*
        |--------------------------------------------------------------------------
        | 8. Report Metadata
        |--------------------------------------------------------------------------
        */

        return [
            'company' => $company,
            'financialYear' => $financialYear,

            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,

            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,

            'netResult' => $netResult,
            'netProfit' => $netProfit,
            'netLoss' => $netLoss,

            'reportTitle' => 'PROFIT & LOSS STATEMENT',

            'periodText' => $periodText,

            'currencyCode' => $company->currency ?: 'BDT',
            'currencySymbol' => $company->currency_symbol ?: '৳',

            'reportDate' => now()->format('d F Y'),

            /*
             * Period selection data for Blade.
             */
            'periodType' => $periodType,
            'selectedMonth' => $selectedMonth,
            'customFrom' => $customFrom,
            'customTo' => $customTo,

            'reportFromDate' => $fromDate->format('Y-m-d'),
            'reportToDate' => $toDate->format('Y-m-d'),

            'financialYearStart' => $financialYearStart->format('Y-m-d'),
            'financialYearEnd' => $financialYearEnd->format('Y-m-d'),

            'periodKey' => $periodKey,

            /*
             * Month options inside the financial year.
             */
            'reportMonths' => $this->getFinancialYearMonths(
                $financialYearStart,
                $financialYearEnd
            ),

            'autoPrint' => false,
        ];
    }

    /**
     * Get accounts and calculate their P&L balances
     * for the selected report period.
     *
     * Income:
     *     Credit - Debit
     *
     * Expense:
     *     Debit - Credit
     *
     * @return Collection<int, Account>
     */
    private function getReportAccounts(
        int $companyId,
        int $financialYearId,
        string $accountType,
        string $normalBalance,
        Carbon $fromDate,
        Carbon $toDate
    ): Collection {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('account_type', $accountType)
            ->where('is_active', true)
            ->with([
                'ledgerEntries' => function ($query) use (
                    $companyId,
                    $financialYearId,
                    $fromDate,
                    $toDate
                ): void {
                    $query
                        ->where('company_id', $companyId)
                        ->where('financial_year_id', $financialYearId)
                        ->where('is_reversed', false)
                        ->whereBetween(
                            'voucher_date',
                            [
                                $fromDate->toDateTimeString(),
                                $toDate->toDateTimeString(),
                            ]
                        );
                },
            ])
            ->orderBy('account_code')
            ->get();

        return $accounts
            ->map(function (Account $account) use ($normalBalance): Account {
                $debit = round(
                    (float) $account->ledgerEntries->sum('debit_amount'),
                    2
                );

                $credit = round(
                    (float) $account->ledgerEntries->sum('credit_amount'),
                    2
                );

                $amount = $normalBalance === 'credit'
                    ? $credit - $debit
                    : $debit - $credit;

                $account->report_amount = round($amount, 2);

                return $account;
            })
            ->values();
    }

    /**
     * Generate selectable months contained inside
     * the current financial year.
     *
     * @return Collection<int, array{
     *     value: string,
     *     label: string,
     *     from: string,
     *     to: string
     * }>
     */
    private function getFinancialYearMonths(
        Carbon $financialYearStart,
        Carbon $financialYearEnd
    ): Collection {
        $months = collect();

        $cursor = $financialYearStart->copy()->startOfMonth();

        while ($cursor->lte($financialYearEnd)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            /*
             * Never allow generated month boundaries
             * to escape the financial year.
             */
            $effectiveStart = $monthStart->lt($financialYearStart)
                ? $financialYearStart->copy()
                : $monthStart;

            $effectiveEnd = $monthEnd->gt($financialYearEnd)
                ? $financialYearEnd->copy()
                : $monthEnd;

            $months->push([
                'value' => $cursor->format('Y-m'),
                'label' => $cursor->format('F Y'),
                'from' => $effectiveStart->format('Y-m-d'),
                'to' => $effectiveEnd->format('Y-m-d'),
            ]);

            $cursor->addMonth()->startOfMonth();
        }

        return $months;
    }
}