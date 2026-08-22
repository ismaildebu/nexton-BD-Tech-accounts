@extends('layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="min-h-screen bg-gray-100 py-6 print:bg-white print:py-0">

    {{-- ============================================================
         SCREEN-ONLY TOOLBAR
    ============================================================= --}}
    <div class="mx-auto mb-5 max-w-5xl px-4 print:hidden">

        <div class="flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm md:flex-row md:items-end md:justify-between">

            <div>
                <h1 class="text-xl font-semibold text-gray-800">
                    Profit & Loss Statement
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $company->company_name }}
                    —
                    Financial Year {{ $financialYear->year_name }}
                </p>
            </div>

            {{-- ====================================================
                 REPORT PERIOD FORM
            ===================================================== --}}
            <form
                method="GET"
                action="{{ route('profit-loss.index') }}"
                id="profit-loss-filter-form"
                class="w-full md:w-auto"
            >

                <div class="flex flex-col gap-3 md:flex-row md:items-end">

                    {{-- Period Type --}}
                    <div>
                        <label
                            for="period"
                            class="mb-1 block text-xs font-semibold text-gray-700"
                        >
                            Report Period
                        </label>

                        <select
                            name="period"
                            id="period"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 md:w-48"
                        >
                            <option
                                value="full"
                                @selected($periodType === 'full')
                            >
                                Full Financial Year
                            </option>

                            <option
                                value="monthly"
                                @selected($periodType === 'monthly')
                            >
                                Monthly
                            </option>

                            <option
                                value="custom"
                                @selected($periodType === 'custom')
                            >
                                Custom Date Range
                            </option>
                        </select>
                    </div>

                    {{-- Monthly Selector --}}
                    <div
                        id="monthly-wrapper"
                        class="{{ $periodType === 'monthly' ? '' : 'hidden' }}"
                    >
                        <label
                            for="month"
                            class="mb-1 block text-xs font-semibold text-gray-700"
                        >
                            Month
                        </label>

                        <select
                            name="month"
                            id="month"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 md:w-44"
                        >
                            <option value="">
                                Select Month
                            </option>

                            @foreach($reportMonths as $month)
                                <option
                                    value="{{ $month['value'] }}"
                                    @selected($selectedMonth === $month['value'])
                                >
                                    {{ $month['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Custom From --}}
                    <div
                        id="custom-from-wrapper"
                        class="{{ $periodType === 'custom' ? '' : 'hidden' }}"
                    >
                        <label
                            for="from"
                            class="mb-1 block text-xs font-semibold text-gray-700"
                        >
                            From
                        </label>

                        <input
                            type="date"
                            name="from"
                            id="from"
                            value="{{ $customFrom }}"
                            min="{{ $financialYearStart }}"
                            max="{{ $financialYearEnd }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </div>

                    {{-- Custom To --}}
                    <div
                        id="custom-to-wrapper"
                        class="{{ $periodType === 'custom' ? '' : 'hidden' }}"
                    >
                        <label
                            for="to"
                            class="mb-1 block text-xs font-semibold text-gray-700"
                        >
                            To
                        </label>

                        <input
                            type="date"
                            name="to"
                            id="to"
                            value="{{ $customTo }}"
                            min="{{ $financialYearStart }}"
                            max="{{ $financialYearEnd }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </div>

                    {{-- Generate --}}
                    <div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 md:w-auto"
                        >
                            Generate Report
                        </button>
                    </div>

                </div>
            </form>

        </div>

        {{-- ====================================================
             ACTION BUTTONS
        ===================================================== --}}
        <div class="mt-3 flex justify-end gap-2">

            <a
                href="{{ route('profit-loss.pdf', request()->query()) }}"
                class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
            >
                Download PDF
            </a>

            <a
                href="{{ route('profit-loss.print', request()->query()) }}"
                target="_blank"
                class="inline-flex items-center rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
            >
                Print
            </a>

        </div>

    </div>


    {{-- ============================================================
         FINANCIAL STATEMENT
    ============================================================= --}}
    <div
        id="profit-loss-statement"
        class="mx-auto max-w-5xl bg-white px-12 py-10 shadow-sm print:max-w-none print:px-10 print:py-6 print:shadow-none"
    >

        {{-- ========================================================
             COMPANY HEADER
        ========================================================= --}}
        <header class="border-b border-gray-800 pb-4 text-center">

            @if(!empty($company->logo))
                <div class="mb-3 flex justify-center">
                    <img
                        src="{{ asset('storage/' . ltrim($company->logo, '/')) }}"
                        alt="{{ $company->company_name }}"
                        class="max-h-20 max-w-40 object-contain"
                    >
                </div>
            @endif

            <h2 class="text-2xl font-bold uppercase tracking-wide text-gray-900">
                {{ $company->company_name }}
            </h2>

            @if($company->address || $company->city)
                <p class="mt-1 text-sm text-gray-600">
                    {{ $company->address }}

                    @if($company->address && $company->city)
                        ,
                    @endif

                    {{ $company->city }}
                </p>
            @endif

            <h1 class="mt-5 text-xl font-bold uppercase tracking-wide text-gray-900">
                {{ $reportTitle }}
            </h1>

            <p class="mt-1 text-sm font-medium text-gray-700">
                {{ $periodText }}
            </p>

        </header>


        {{-- ========================================================
             STATEMENT TABLE
        ========================================================= --}}
        <div class="mt-7">

            <table class="w-full border-collapse text-sm">

                <thead>
                    <tr class="border-b-2 border-gray-800">

                        <th
                            class="w-3/4 px-2 py-2 text-left font-bold text-gray-900"
                        >
                            Particulars
                        </th>

                        <th
                            class="w-1/4 px-2 py-2 text-right font-bold text-gray-900"
                        >
                            Amount ({{ $currencyCode }})
                        </th>

                    </tr>
                </thead>

                <tbody>

                    {{-- =================================================
                         REVENUE / INCOME
                    ================================================== --}}
                    <tr>
                        <td
                            colspan="2"
                            class="px-2 pb-2 pt-6 font-bold uppercase tracking-wide text-gray-900"
                        >
                            Revenue / Income
                        </td>
                    </tr>

                    @forelse($incomeAccounts as $account)

                        <tr class="border-b border-gray-100">

                            <td class="px-2 py-1.5 pl-6 text-gray-800">
                                {{ $account->account_name }}
                            </td>

                            <td class="px-2 py-1.5 text-right tabular-nums text-gray-800">

                                @if(abs((float) $account->report_amount) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_amount, 2) }}
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="2"
                                class="px-2 py-3 text-center text-gray-500"
                            >
                                No income accounts found.
                            </td>
                        </tr>

                    @endforelse


                    {{-- TOTAL REVENUE --}}
                    <tr class="border-t border-gray-800">

                        <td class="px-2 py-2 font-bold uppercase">
                            Total Revenue
                        </td>

                        <td class="px-2 py-2 text-right font-bold tabular-nums">
                            {{ number_format((float) $totalIncome, 2) }}
                        </td>

                    </tr>


                    {{-- =================================================
                         EXPENSES
                    ================================================== --}}
                    <tr>

                        <td
                            colspan="2"
                            class="px-2 pb-2 pt-8 font-bold uppercase tracking-wide text-gray-900"
                        >
                            Expenses
                        </td>

                    </tr>

                    @forelse($expenseAccounts as $account)

                        <tr class="border-b border-gray-100">

                            <td class="px-2 py-1.5 pl-6 text-gray-800">
                                {{ $account->account_name }}
                            </td>

                            <td class="px-2 py-1.5 text-right tabular-nums text-gray-800">

                                @if(abs((float) $account->report_amount) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_amount, 2) }}
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="2"
                                class="px-2 py-3 text-center text-gray-500"
                            >
                                No expense accounts found.
                            </td>
                        </tr>

                    @endforelse


                    {{-- TOTAL EXPENSES --}}
                    <tr class="border-t border-gray-800">

                        <td class="px-2 py-2 font-bold uppercase">
                            Total Expenses
                        </td>

                        <td class="px-2 py-2 text-right font-bold tabular-nums">
                            {{ number_format((float) $totalExpense, 2) }}
                        </td>

                    </tr>


                    {{-- =================================================
                         NET RESULT
                    ================================================== --}}
                    <tr>

                        <td colspan="2" class="pt-8">

                            <div class="border-y-2 border-gray-900 py-3">

                                <div class="flex items-center justify-between px-2">

                                    <span class="font-bold uppercase tracking-wide">

                                        @if($netProfit > 0)
                                            Net Profit
                                        @elseif($netLoss > 0)
                                            Net Loss
                                        @else
                                            Net Profit / Loss
                                        @endif

                                    </span>

                                    <span class="font-bold tabular-nums">
                                        {{ number_format(abs((float) $netResult), 2) }}
                                    </span>

                                </div>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>


        {{-- ========================================================
             SIGNATURE SECTION
        ========================================================= --}}
        <div class="mt-20 print:mt-24">

            <div class="grid grid-cols-2 gap-x-20 gap-y-12">

                <div>
                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-sm text-gray-800">
                        Prepared By
                    </p>
                </div>

                <div>
                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-sm text-gray-800">
                        Checked By
                    </p>
                </div>

                <div>
                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-sm text-gray-800">
                        Authorized By
                    </p>
                </div>

                <div>
                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-sm text-gray-800">
                        Date
                    </p>
                </div>

            </div>

        </div>


        {{-- ========================================================
             FOOTER
        ========================================================= --}}
        <footer class="mt-12 border-t border-gray-300 pt-3 text-center text-xs text-gray-500">

            <p>
                This statement is prepared from the accounting records of
                {{ $company->company_name }}.
            </p>

            <p class="mt-1">
                Financial Year: {{ $financialYear->year_name }}
            </p>

            <p class="mt-1">
                Report Period: {{ $periodText }}
            </p>

        </footer>

    </div>
</div>


{{-- ================================================================
     PERIOD SELECTION JAVASCRIPT
================================================================ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const periodSelect = document.getElementById('period');

    const monthlyWrapper = document.getElementById('monthly-wrapper');

    const customFromWrapper = document.getElementById('custom-from-wrapper');

    const customToWrapper = document.getElementById('custom-to-wrapper');

    const monthInput = document.getElementById('month');

    const fromInput = document.getElementById('from');

    const toInput = document.getElementById('to');


    function updatePeriodFields() {

        const period = periodSelect.value;

        const isMonthly = period === 'monthly';

        const isCustom = period === 'custom';


        monthlyWrapper.classList.toggle(
            'hidden',
            !isMonthly
        );

        customFromWrapper.classList.toggle(
            'hidden',
            !isCustom
        );

        customToWrapper.classList.toggle(
            'hidden',
            !isCustom
        );


        /*
         * Required fields only apply to the selected period.
         */
        monthInput.required = isMonthly;

        fromInput.required = isCustom;

        toInput.required = isCustom;


        if (!isMonthly) {
            monthInput.required = false;
        }

        if (!isCustom) {
            fromInput.required = false;
            toInput.required = false;
        }
    }


    periodSelect.addEventListener(
        'change',
        updatePeriodFields
    );


    updatePeriodFields();
});
</script>


{{-- ================================================================
     AUTO PRINT
================================================================ --}}
@if($autoPrint ?? false)
<script>
window.addEventListener('load', function () {

    setTimeout(function () {
        window.print();
    }, 500);

});
</script>
@endif


<style>
    @media print {

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        html,
        body {
            background: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #profit-loss-statement {
            width: 100% !important;
            max-width: none !important;
        }

        tr,
        td,
        th {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        header {
            break-after: avoid;
            page-break-after: avoid;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }
    }
</style>
@endsection