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

class TrialBalanceController extends Controller
{
    /**
     * Display Trial Balance.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('trial-balance.index', $context);
    }

    /**
     * Print-friendly Trial Balance.
     */
    public function print(Request $request): View|RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $context['autoPrint'] = true;

        return view('trial-balance.index', $context);
    }

    /**
     * Download Trial Balance as PDF.
     */
    public function downloadPdf(Request $request): Response
    {
        $context = $this->resolveContext($request);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $pdf = Pdf::loadView('trial-balance.pdf', $context);

        $pdf->setPaper('A4', 'landscape');

        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $companySlug = str($context['company']->company_name)
            ->slug('-')
            ->toString();

        $periodSlug = str($context['periodSlug'])
            ->slug('-')
            ->toString();

        $filename = "trial-balance-{$companySlug}-{$periodSlug}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Resolve report context.
     *
     * Trial Balance is calculated as of the selected period end.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function resolveContext(
        Request $request
    ): array|RedirectResponse {
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

        $financialYearId = $request->integer(
            'financial_year_id',
            (int) session('financial_year_id')
        );

        $financialYear = null;

        if ($financialYearId > 0) {
            $financialYear = FinancialYear::query()
                ->where('company_id', $company->id)
                ->whereKey($financialYearId)
                ->first();
        }

        /*
         * If no explicit year was selected, use the session year.
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
                    'Please select or create a financial year first.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Financial Year Dates
        |--------------------------------------------------------------------------
        */

        $yearStart = Carbon::parse($financialYear->start_date)
            ->startOfDay();

        $yearEnd = Carbon::parse($financialYear->end_date)
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | 4. Resolve Period
        |--------------------------------------------------------------------------
        */

        $periodType = $request->input('period', 'year');

        if (! in_array(
            $periodType,
            ['year', 'month', 'custom'],
            true
        )) {
            $periodType = 'year';
        }

        $periodStart = $yearStart->copy();
        $periodEnd = $yearEnd->copy();

        if ($periodType === 'month') {
            $month = $request->input('month');

            try {
                $selectedMonth = Carbon::createFromFormat(
                    'Y-m',
                    (string) $month
                )->startOfMonth();
            } catch (\Throwable) {
                $selectedMonth = $yearStart->copy()->startOfMonth();
            }

            $periodStart = $selectedMonth->copy()->startOfMonth();
            $periodEnd = $selectedMonth->copy()->endOfMonth();

            /*
             * Month must belong to selected financial year.
             */
            if (
                $periodStart->lt($yearStart)
                || $periodEnd->gt($yearEnd)
            ) {
                return redirect()
                    ->route('trial-balance.index', [
                        'financial_year_id' => $financialYear->id,
                        'period' => 'year',
                    ])
                    ->with(
                        'error',
                        'Selected month is outside the financial year.'
                    );
            }
        }

        if ($periodType === 'custom') {
            try {
                $customStart = Carbon::parse(
                    (string) $request->input('from')
                )->startOfDay();

                $customEnd = Carbon::parse(
                    (string) $request->input('to')
                )->endOfDay();
            } catch (\Throwable) {
                return redirect()
                    ->route('trial-balance.index', [
                        'financial_year_id' => $financialYear->id,
                        'period' => 'year',
                    ])
                    ->with(
                        'error',
                        'Please provide a valid custom date range.'
                    );
            }

            if ($customStart->gt($customEnd)) {
                return redirect()
                    ->route('trial-balance.index', [
                        'financial_year_id' => $financialYear->id,
                        'period' => 'year',
                    ])
                    ->with(
                        'error',
                        'The start date cannot be after the end date.'
                    );
            }

            if (
                $customStart->lt($yearStart)
                || $customEnd->gt($yearEnd)
            ) {
                return redirect()
                    ->route('trial-balance.index', [
                        'financial_year_id' => $financialYear->id,
                        'period' => 'year',
                    ])
                    ->with(
                        'error',
                        'Custom dates must remain inside the selected financial year.'
                    );
            }

            $periodStart = $customStart;
            $periodEnd = $customEnd;
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Keep Context Synchronized
        |--------------------------------------------------------------------------
        */

        session([
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'financial_year_id' => $financialYear->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. Load Accounts
        |--------------------------------------------------------------------------
        */

        $accounts = Account::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->with([
                'ledgerEntries' => function ($query) use (
                    $company,
                    $financialYear,
                    $yearStart,
                    $periodStart,
                    $periodEnd
                ): void {
                    $query
                        ->where('company_id', $company->id)
                        ->where(
                            'financial_year_id',
                            $financialYear->id
                        )
                        ->where('is_reversed', false)
                        ->whereBetween(
                            'entry_date',
                            [
                                $yearStart->toDateString(),
                                $periodEnd->toDateString(),
                            ]
                        );
                },
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7. Calculate Trial Balance
        |--------------------------------------------------------------------------
        |
        | Opening:
        |     Account opening_balance
        |
        | Period:
        |     Entries between selected period start/end.
        |
        | Closing:
        |     Opening + all entries from FY start through period end.
        |
        | Debit-normal:
        |     Asset / Expense
        |
        | Credit-normal:
        |     Liability / Equity / Income
        |
        */

        $rows = $accounts
            ->map(function (Account $account) use (
                $yearStart,
                $periodStart,
                $periodEnd
            ): Account {
                $entries = $account->ledgerEntries;

                $periodEntries = $entries->filter(
                    function ($entry) use (
                        $periodStart,
                        $periodEnd
                    ): bool {
                        if (! $entry->entry_date) {
                            return false;
                        }

                        $date = Carbon::parse($entry->entry_date);

                        return $date->betweenIncluded(
                            $periodStart,
                            $periodEnd
                        );
                    }
                );

                $periodDebit = round(
                    (float) $periodEntries->sum('debit_amount'),
                    2
                );

                $periodCredit = round(
                    (float) $periodEntries->sum('credit_amount'),
                    2
                );

                /*
                 * Entries before selected period.
                 */
                $priorEntries = $entries->filter(
                    function ($entry) use (
                        $yearStart,
                        $periodStart
                    ): bool {
                        if (! $entry->entry_date) {
                            return false;
                        }

                        $date = Carbon::parse($entry->entry_date);

                        return $date->gte($yearStart)
                            && $date->lt($periodStart);
                    }
                );

                $priorDebit = round(
                    (float) $priorEntries->sum('debit_amount'),
                    2
                );

                $priorCredit = round(
                    (float) $priorEntries->sum('credit_amount'),
                    2
                );

                $openingBalance = round(
                    (float) $account->opening_balance,
                    2
                );

                /*
                 * Opening net balance.
                 */
                $isDebitNormal = in_array(
                    $account->account_type,
                    [
                        Account::TYPE_ASSET,
                        Account::TYPE_EXPENSE,
                    ],
                    true
                );

                if ($isDebitNormal) {
                    $openingNet = $openingBalance
                        + $priorDebit
                        - $priorCredit;

                    $closingNet = $openingNet
                        + $periodDebit
                        - $periodCredit;
                } else {
                    $openingNet = $openingBalance
                        + $priorCredit
                        - $priorDebit;

                    $closingNet = $openingNet
                        + $periodCredit
                        - $periodDebit;
                }

                /*
                 * Closing debit/credit presentation.
                 */
                if ($isDebitNormal) {
                    $closingDebit = max($closingNet, 0);
                    $closingCredit = max(-$closingNet, 0);
                } else {
                    $closingCredit = max($closingNet, 0);
                    $closingDebit = max(-$closingNet, 0);
                }

                /*
                 * Opening debit/credit presentation.
                 */
                if ($isDebitNormal) {
                    $openingDebit = max($openingNet, 0);
                    $openingCredit = max(-$openingNet, 0);
                } else {
                    $openingCredit = max($openingNet, 0);
                    $openingDebit = max(-$openingNet, 0);
                }

                $account->report_opening_debit = round(
                    $openingDebit,
                    2
                );

                $account->report_opening_credit = round(
                    $openingCredit,
                    2
                );

                $account->report_period_debit = $periodDebit;
                $account->report_period_credit = $periodCredit;

                $account->report_closing_debit = round(
                    $closingDebit,
                    2
                );

                $account->report_closing_credit = round(
                    $closingCredit,
                    2
                );

                $account->report_closing_balance = round(
                    $closingNet,
                    2
                );

                return $account;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 8. Totals
        |--------------------------------------------------------------------------
        */

        $totalOpeningDebit = round(
            (float) $rows->sum('report_opening_debit'),
            2
        );

        $totalOpeningCredit = round(
            (float) $rows->sum('report_opening_credit'),
            2
        );

        $totalPeriodDebit = round(
            (float) $rows->sum('report_period_debit'),
            2
        );

        $totalPeriodCredit = round(
            (float) $rows->sum('report_period_credit'),
            2
        );

        $totalClosingDebit = round(
            (float) $rows->sum('report_closing_debit'),
            2
        );

        $totalClosingCredit = round(
            (float) $rows->sum('report_closing_credit'),
            2
        );

        $balanceDifference = round(
            $totalClosingDebit - $totalClosingCredit,
            2
        );

        $isBalanced = abs($balanceDifference) < 0.01;

        /*
        |--------------------------------------------------------------------------
        | 9. Period Text
        |--------------------------------------------------------------------------
        */

        if ($periodType === 'month') {
            $periodText = sprintf(
                'For the month ended %s',
                $periodEnd->format('d F Y')
            );

            $periodSlug = $periodStart->format('Y-m');
        } elseif ($periodType === 'custom') {
            $periodText = sprintf(
                'For the period %s to %s',
                $periodStart->format('d F Y'),
                $periodEnd->format('d F Y')
            );

            $periodSlug = sprintf(
                '%s-to-%s',
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            );
        } else {
            $periodText = sprintf(
                'For the year ended %s',
                $yearEnd->format('d F Y')
            );

            $periodSlug = $financialYear->year_name;
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Return View Context
        |--------------------------------------------------------------------------
        */

        return [
            'company' => $company,
            'financialYear' => $financialYear,

            'accounts' => $rows,

            'periodType' => $periodType,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,

            'periodText' => $periodText,
            'periodSlug' => $periodSlug,

            'totalOpeningDebit' => $totalOpeningDebit,
            'totalOpeningCredit' => $totalOpeningCredit,

            'totalPeriodDebit' => $totalPeriodDebit,
            'totalPeriodCredit' => $totalPeriodCredit,

            'totalClosingDebit' => $totalClosingDebit,
            'totalClosingCredit' => $totalClosingCredit,

            'balanceDifference' => $balanceDifference,
            'isBalanced' => $isBalanced,

            'currencyCode' => $company->currency ?: 'BDT',
            'currencySymbol' => $company->currency_symbol ?: '৳',

            'reportTitle' => 'TRIAL BALANCE',

            'reportDate' => now()->format('d F Y'),

            'autoPrint' => false,
        ];
    }
}