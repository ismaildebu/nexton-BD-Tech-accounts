<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FinancialYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BalanceSheetController extends Controller
{
    /**
     * Display the Balance Sheet.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $context = $this->buildReportContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('balance-sheet.index', $context);
    }

    /**
     * Print-friendly Balance Sheet.
     */
    public function print(Request $request): View|RedirectResponse
    {
        $context = $this->buildReportContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('balance-sheet.print', $context);
    }

    /**
     * Download Balance Sheet as PDF.
     */
    public function pdf(Request $request): Response|RedirectResponse
    {
        $context = $this->buildReportContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $pdf = Pdf::loadView(
            'balance-sheet.pdf',
            $context
        );

        $pdf->setPaper('A4', 'portrait');

        $companyName = $context['company']?->name ?? 'Company';

        $safeCompanyName = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $companyName
        );

        $safeCompanyName = trim(
            (string) $safeCompanyName,
            '-'
        );

        if ($safeCompanyName === '') {
            $safeCompanyName = 'Company';
        }

        $financialYearId = $context['financialYearId'] ?: 'current';

        $fileName = 'balance-sheet-'
            . strtolower($safeCompanyName)
            . '-'
            . $financialYearId
            . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Build complete Balance Sheet report context.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function buildReportContext(
        Request $request
    ): array|RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $companyId = (int) session('company_id');

        if ($companyId <= 0) {
            return redirect()
                ->route('dashboard.index')
                ->with(
                    'error',
                    'Please select a company first.'
                );
        }

        $company = \App\Models\Company::query()
            ->find($companyId);

        if (! $company) {
            return redirect()
                ->route('dashboard.index')
                ->with(
                    'error',
                    'Selected company could not be found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Financial Years
        |--------------------------------------------------------------------------
        */

        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Selected Financial Year
        |--------------------------------------------------------------------------
        */

        $financialYearId = $request->integer(
            'financial_year_id',
            (int) session('financial_year_id')
        );

        if ($financialYearId <= 0) {
            $financialYearId = (int) optional(
                $financialYears->first()
            )->id;
        }

        $selectedYear = $financialYears->firstWhere(
            'id',
            $financialYearId
        );

        /*
        |--------------------------------------------------------------------------
        | If Requested Financial Year Does Not Belong To Company
        |--------------------------------------------------------------------------
        */

        if (! $selectedYear && $financialYears->isNotEmpty()) {
            $selectedYear = $financialYears->first();
            $financialYearId = (int) $selectedYear->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Report Date
        |--------------------------------------------------------------------------
        |
        | Balance Sheet is a point-in-time report.
        |
        */

        if ($selectedYear?->end_date) {
            $reportDate = Carbon::parse(
                $selectedYear->end_date
            )->format('d F Y');
        } else {
            $reportDate = now()->format('d F Y');
        }

        /*
        |--------------------------------------------------------------------------
        | Ledger Entry Constraint
        |--------------------------------------------------------------------------
        */

        $entryConstraint = function ($query) use (
            $companyId,
            $financialYearId
        ): void {
            $query
                ->where('company_id', $companyId)
                ->where('is_reversed', false)
                ->when(
                    $financialYearId > 0,
                    fn ($query) => $query->where(
                        'financial_year_id',
                        $financialYearId
                    )
                );
        };

        /*
        |--------------------------------------------------------------------------
        | Account Loader
        |--------------------------------------------------------------------------
        */

        $loadAccounts = function (
            string $type
        ) use (
            $companyId,
            $entryConstraint
        ) {
            return Account::query()
                ->with([
                    'ledgerEntries' => $entryConstraint,
                ])
                ->where('company_id', $companyId)
                ->where('account_type', $type)
                ->where('is_active', true)
                ->orderBy('account_code')
                ->get();
        };

        /*
        |--------------------------------------------------------------------------
        | Load Accounts
        |--------------------------------------------------------------------------
        */

        $assets = $loadAccounts(
            Account::TYPE_ASSET
        );

        $liabilities = $loadAccounts(
            Account::TYPE_LIABILITY
        );

        $equity = $loadAccounts(
            Account::TYPE_EQUITY
        );

        $income = $loadAccounts(
            Account::TYPE_INCOME
        );

        $expense = $loadAccounts(
            Account::TYPE_EXPENSE
        );

        /*
        |--------------------------------------------------------------------------
        | Account Classification
        |--------------------------------------------------------------------------
        |
        | We support common account naming conventions.
        |
        | If your Account model already has a dedicated
        | current/non-current field, this can later be
        | changed to use that field.
        |
        */

        $isCurrentAccount = static function (
            Account $account
        ): bool {
            $name = strtolower(
                trim((string) (
                    $account->account_name
                    ?? $account->name
                    ?? ''
                ))
            );

            $code = (string) (
                $account->account_code ?? ''
            );

            /*
            | Common current asset keywords.
            */

            $currentAssetKeywords = [
                'cash',
                'bank',
                'receivable',
                'receivables',
                'debtor',
                'debtors',
                'inventory',
                'stock',
                'prepaid',
                'advance',
                'current asset',
                'current assets',
                'accounts receivable',
            ];

            /*
            | Common non-current asset keywords.
            */

            $nonCurrentAssetKeywords = [
                'fixed asset',
                'fixed assets',
                'property',
                'plant',
                'equipment',
                'machinery',
                'building',
                'land',
                'vehicle',
                'furniture',
                'intangible',
                'long term investment',
                'long-term investment',
            ];

            /*
            | Common current liability keywords.
            */

            $currentLiabilityKeywords = [
                'payable',
                'payables',
                'creditor',
                'creditors',
                'supplier',
                'suppliers',
                'accounts payable',
                'short term',
                'short-term',
                'current liability',
                'current liabilities',
                'accrued',
                'tax payable',
                'salary payable',
                'expense payable',
            ];

            /*
            | Common non-current liability keywords.
            */

            $nonCurrentLiabilityKeywords = [
                'long term loan',
                'long-term loan',
                'long term liability',
                'long-term liability',
                'mortgage',
                'lease liability',
                'debenture',
                'deferred tax',
            ];

            if ($account->account_type === Account::TYPE_ASSET) {
                foreach ($nonCurrentAssetKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return false;
                    }
                }

                foreach ($currentAssetKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return true;
                    }
                }

                /*
                | Default asset classification:
                | Use account code where possible.
                |
                | Codes beginning with 1 are generally assets.
                | 11/12 are commonly current assets in many COAs,
                | but this remains a fallback only.
                */

                if (str_starts_with($code, '11')) {
                    return true;
                }

                if (str_starts_with($code, '12')) {
                    return true;
                }

                /*
                | If no classification is available,
                | treat the asset as current.
                */

                return true;
            }

            if (
                $account->account_type
                === Account::TYPE_LIABILITY
            ) {
                foreach ($nonCurrentLiabilityKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return false;
                    }
                }

                foreach ($currentLiabilityKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return true;
                    }
                }

                /*
                | Default liability classification:
                | current.
                */

                return true;
            }

            return false;
        };

        /*
        |--------------------------------------------------------------------------
        | Non-Current Assets
        |--------------------------------------------------------------------------
        */

        $nonCurrentAssets = $assets
            ->filter(
                fn (Account $account): bool =>
                    ! $isCurrentAccount($account)
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Current Assets
        |--------------------------------------------------------------------------
        */

        $currentAssets = $assets
            ->filter(
                fn (Account $account): bool =>
                    $isCurrentAccount($account)
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Non-Current Liabilities
        |--------------------------------------------------------------------------
        */

        $nonCurrentLiabilities = $liabilities
            ->filter(
                fn (Account $account): bool =>
                    ! $isCurrentAccount($account)
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Current Liabilities
        |--------------------------------------------------------------------------
        */

        $currentLiabilities = $liabilities
            ->filter(
                fn (Account $account): bool =>
                    $isCurrentAccount($account)
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Balance Calculator
        |--------------------------------------------------------------------------
        |
        | Asset / Expense:
        |     Opening + Debit - Credit
        |
        | Liability / Equity / Income:
        |     Opening + Credit - Debit
        |
        */

        $calculateBalance = static function (
            Account $account
        ): float {
            $debit = (float) $account
                ->ledgerEntries
                ->sum('debit_amount');

            $credit = (float) $account
                ->ledgerEntries
                ->sum('credit_amount');

            $opening = (float) (
                $account->opening_balance ?? 0
            );

            if (
                in_array(
                    $account->account_type,
                    [
                        Account::TYPE_ASSET,
                        Account::TYPE_EXPENSE,
                    ],
                    true
                )
            ) {
                return $opening
                    + $debit
                    - $credit;
            }

            return $opening
                + $credit
                - $debit;
        };

        /*
        |--------------------------------------------------------------------------
        | Asset Totals
        |--------------------------------------------------------------------------
        */

        $totalNonCurrentAssets = $nonCurrentAssets->sum(
            $calculateBalance
        );

        $totalCurrentAssets = $currentAssets->sum(
            $calculateBalance
        );

        $totalAssets =
            $totalNonCurrentAssets
            + $totalCurrentAssets;

        /*
        |--------------------------------------------------------------------------
        | Liability Totals
        |--------------------------------------------------------------------------
        */

        $totalNonCurrentLiabilities =
            $nonCurrentLiabilities->sum(
                $calculateBalance
            );

        $totalCurrentLiabilities =
            $currentLiabilities->sum(
                $calculateBalance
            );

        $totalLiabilities =
            $totalNonCurrentLiabilities
            + $totalCurrentLiabilities;

        /*
        |--------------------------------------------------------------------------
        | Equity
        |--------------------------------------------------------------------------
        */

        $totalEquity = $equity->sum(
            $calculateBalance
        );

        /*
        |--------------------------------------------------------------------------
        | Income
        |--------------------------------------------------------------------------
        */

        $totalIncome = $income->sum(
            $calculateBalance
        );

        /*
        |--------------------------------------------------------------------------
        | Expense
        |--------------------------------------------------------------------------
        */

        $totalExpense = $expense->sum(
            $calculateBalance
        );

        /*
        |--------------------------------------------------------------------------
        | Current Year Profit / Loss
        |--------------------------------------------------------------------------
        */

        $currentProfit =
            $totalIncome
            - $totalExpense;

        /*
        |--------------------------------------------------------------------------
        | Total Equity Including Current Year Profit / Loss
        |--------------------------------------------------------------------------
        */

        $totalEquityIncludingCurrentYear =
            $totalEquity
            + $currentProfit;

        /*
        |--------------------------------------------------------------------------
        | Alias Used By Existing Blade
        |--------------------------------------------------------------------------
        */

        $totalEquityIncludingProfit =
            $totalEquityIncludingCurrentYear;

        /*
        |--------------------------------------------------------------------------
        | Total Liabilities + Equity
        |--------------------------------------------------------------------------
        */

        $totalLiabilitiesAndEquity =
            $totalLiabilities
            + $totalEquityIncludingCurrentYear;

        /*
        |--------------------------------------------------------------------------
        | Balance Difference
        |--------------------------------------------------------------------------
        */

        $difference = abs(
            $totalAssets
            - $totalLiabilitiesAndEquity
        );

        /*
        |--------------------------------------------------------------------------
        | Balance Status
        |--------------------------------------------------------------------------
        */

        $isBalanced = $difference < 0.01;

        /*
        |--------------------------------------------------------------------------
        | Report Context
        |--------------------------------------------------------------------------
        */

        return [
            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

            'company' => $company,

            'companyId' => $companyId,

            /*
            |--------------------------------------------------------------------------
            | Accounts
            |--------------------------------------------------------------------------
            */

            'assets' => $assets,

            'nonCurrentAssets' => $nonCurrentAssets,

            'currentAssets' => $currentAssets,

            'liabilities' => $liabilities,

            'nonCurrentLiabilities' =>
                $nonCurrentLiabilities,

            'currentLiabilities' =>
                $currentLiabilities,

            'equity' => $equity,

            'income' => $income,

            'expense' => $expense,

            /*
            |--------------------------------------------------------------------------
            | Financial Year
            |--------------------------------------------------------------------------
            */

            'financialYears' => $financialYears,

            'selectedYear' => $selectedYear,

            'financialYearId' => $financialYearId,

            /*
            |--------------------------------------------------------------------------
            | Report Information
            |--------------------------------------------------------------------------
            */

            'reportDate' => $reportDate,

            /*
            |--------------------------------------------------------------------------
            | Calculation Helper
            |--------------------------------------------------------------------------
            */

            'calculateBalance' => $calculateBalance,

            /*
            |--------------------------------------------------------------------------
            | Asset Totals
            |--------------------------------------------------------------------------
            */

            'totalNonCurrentAssets' =>
                $totalNonCurrentAssets,

            'totalCurrentAssets' =>
                $totalCurrentAssets,

            'totalAssets' =>
                $totalAssets,

            /*
            |--------------------------------------------------------------------------
            | Liability Totals
            |--------------------------------------------------------------------------
            */

            'totalNonCurrentLiabilities' =>
                $totalNonCurrentLiabilities,

            'totalCurrentLiabilities' =>
                $totalCurrentLiabilities,

            'totalLiabilities' =>
                $totalLiabilities,

            /*
            |--------------------------------------------------------------------------
            | Equity / Income / Expense
            |--------------------------------------------------------------------------
            */

            'totalEquity' =>
                $totalEquity,

            'totalIncome' =>
                $totalIncome,

            'totalExpense' =>
                $totalExpense,

            /*
            |--------------------------------------------------------------------------
            | Current Year Result
            |--------------------------------------------------------------------------
            */

            'currentProfit' =>
                $currentProfit,

            /*
            |--------------------------------------------------------------------------
            | Final Equity
            |--------------------------------------------------------------------------
            */

            'totalEquityIncludingCurrentYear' =>
                $totalEquityIncludingCurrentYear,

            'totalEquityIncludingProfit' =>
                $totalEquityIncludingProfit,

            /*
            |--------------------------------------------------------------------------
            | Final Balance Sheet Total
            |--------------------------------------------------------------------------
            */

            'totalLiabilitiesAndEquity' =>
                $totalLiabilitiesAndEquity,

            /*
            |--------------------------------------------------------------------------
            | Balance Check
            |--------------------------------------------------------------------------
            */

            'difference' =>
                $difference,

            'isBalanced' =>
                $isBalanced,
        ];
    }
}