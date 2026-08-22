@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')

<div class="min-h-screen bg-gray-100 py-6 print:bg-white print:py-0">

    {{-- ================================================================
         SCREEN TOOLBAR
    ================================================================= --}}
    <div class="mx-auto mb-5 max-w-7xl px-4 print:hidden">

        <div class="rounded-xl bg-white p-5 shadow-sm">

            <div class="flex flex-wrap items-center justify-between gap-4">

                <div>
                    <h1 class="text-xl font-semibold text-gray-800">
                        Trial Balance
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $company->company_name }}
                        —
                        {{ $financialYear->year_name }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">

                    @php
                        $reportQuery = [
                            'financial_year_id' => $financialYear->id,
                            'period' => $periodType,
                        ];

                        if ($periodType === 'month') {
                            $reportQuery['month'] = $periodStart->format('Y-m');
                        }

                        if ($periodType === 'custom') {
                            $reportQuery['from'] = $periodStart->format('Y-m-d');
                            $reportQuery['to'] = $periodEnd->format('Y-m-d');
                        }
                    @endphp

                    <a
                        href="{{ route('trial-balance.pdf', $reportQuery) }}"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    >
                        Download PDF
                    </a>

                    <a
                        href="{{ route('trial-balance.print', $reportQuery) }}"
                        target="_blank"
                        class="inline-flex items-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900"
                    >
                        Print
                    </a>

                </div>

            </div>


            {{-- ============================================================
                 PERIOD FILTER
            ============================================================= --}}

            <form
                method="GET"
                action="{{ route('trial-balance.index') }}"
                class="mt-5 border-t border-gray-200 pt-5"
                id="trial-balance-filter"
            >

                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                    {{-- Financial Year --}}
                    <div>

                        <label
                            for="financial_year_id"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Financial Year
                        </label>

                        <select
                            name="financial_year_id"
                            id="financial_year_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="document.getElementById('trial-balance-filter').submit()"
                        >

                            @php
                                $financialYears = \App\Models\FinancialYear::query()
                                    ->where('company_id', $company->id)
                                    ->orderByDesc('start_date')
                                    ->get();
                            @endphp

                            @foreach($financialYears as $year)

                                <option
                                    value="{{ $year->id }}"
                                    {{ $year->id == $financialYear->id ? 'selected' : '' }}
                                >
                                    {{ $year->year_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Period --}}
                    <div>

                        <label
                            for="period"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Report Period
                        </label>

                        <select
                            name="period"
                            id="period"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="toggleTrialBalancePeriodFields()"
                        >

                            <option
                                value="year"
                                {{ $periodType === 'year' ? 'selected' : '' }}
                            >
                                Full Financial Year
                            </option>

                            <option
                                value="month"
                                {{ $periodType === 'month' ? 'selected' : '' }}
                            >
                                Monthly
                            </option>

                            <option
                                value="custom"
                                {{ $periodType === 'custom' ? 'selected' : '' }}
                            >
                                Custom Date Range
                            </option>

                        </select>

                    </div>


                    {{-- Month --}}
                    <div id="month-field">

                        <label
                            for="month"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Month
                        </label>

                        <input
                            type="month"
                            name="month"
                            id="month"
                            value="{{ $periodStart->format('Y-m') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >

                    </div>


                    {{-- Custom From --}}
                    <div id="custom-from-field">

                        <label
                            for="from"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            From
                        </label>

                        <input
                            type="date"
                            name="from"
                            id="from"
                            value="{{ $periodStart->format('Y-m-d') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >

                    </div>

                </div>


                {{-- Custom To --}}
                <div
                    id="custom-to-field"
                    class="mt-4 max-w-xs"
                >

                    <label
                        for="to"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        To
                    </label>

                    <input
                        type="date"
                        name="to"
                        id="to"
                        value="{{ $periodEnd->format('Y-m-d') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                </div>


                <div class="mt-4">

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Generate Trial Balance
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ================================================================
         STATEMENT
    ================================================================= --}}

    <div
        id="trial-balance-statement"
        class="mx-auto max-w-7xl bg-white px-8 py-8 shadow-sm print:max-w-none print:px-8 print:py-5 print:shadow-none"
    >

        {{-- Header --}}
        <header class="border-b border-gray-800 pb-4 text-center">

            @if(!empty($company->logo))

                <div class="mb-3 flex justify-center">

                    <img
                        src="{{ asset('storage/' . ltrim($company->logo, '/')) }}"
                        alt="{{ $company->company_name }}"
                        class="max-h-16 max-w-40 object-contain"
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


            <h1 class="mt-4 text-xl font-bold uppercase tracking-wide text-gray-900">
                {{ $reportTitle }}
            </h1>


            <p class="mt-1 text-sm font-medium text-gray-700">
                {{ $periodText }}
            </p>


            <p class="mt-1 text-xs text-gray-500">
                Financial Year: {{ $financialYear->year_name }}
            </p>

        </header>


        {{-- ============================================================
             TABLE
        ============================================================= --}}

        <div class="mt-6 overflow-x-auto">

            <table class="w-full border-collapse text-xs">

                <thead>

                    <tr class="border-b-2 border-gray-900">

                        <th
                            rowspan="2"
                            class="border border-gray-700 px-2 py-2 text-left"
                        >
                            Code
                        </th>

                        <th
                            rowspan="2"
                            class="border border-gray-700 px-2 py-2 text-left"
                        >
                            Account
                        </th>

                        <th
                            colspan="2"
                            class="border border-gray-700 px-2 py-2 text-center"
                        >
                            Opening Balance
                        </th>

                        <th
                            colspan="2"
                            class="border border-gray-700 px-2 py-2 text-center"
                        >
                            Period Movement
                        </th>

                        <th
                            colspan="2"
                            class="border border-gray-700 px-2 py-2 text-center"
                        >
                            Closing Balance
                        </th>

                    </tr>


                    <tr class="border-b-2 border-gray-900">

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Debit
                        </th>

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Credit
                        </th>

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Debit
                        </th>

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Credit
                        </th>

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Debit
                        </th>

                        <th class="border border-gray-700 px-2 py-1 text-right">
                            Credit
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($accounts as $account)

                        <tr class="border-b border-gray-200">

                            <td class="border-l border-gray-300 px-2 py-1.5 text-gray-600">
                                {{ $account->account_code }}
                            </td>

                            <td class="px-2 py-1.5 text-gray-800">
                                {{ $account->account_name }}

                                <span class="ml-1 text-[10px] text-gray-400">
                                    {{ $account->account_type }}
                                </span>
                            </td>


                            {{-- Opening Debit --}}
                            <td class="border-l border-gray-300 px-2 py-1.5 text-right tabular-nums">
                                @if(abs((float) $account->report_opening_debit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_opening_debit, 2) }}
                                @endif
                            </td>


                            {{-- Opening Credit --}}
                            <td class="px-2 py-1.5 text-right tabular-nums">
                                @if(abs((float) $account->report_opening_credit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_opening_credit, 2) }}
                                @endif
                            </td>


                            {{-- Period Debit --}}
                            <td class="border-l border-gray-300 px-2 py-1.5 text-right tabular-nums">
                                @if(abs((float) $account->report_period_debit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_period_debit, 2) }}
                                @endif
                            </td>


                            {{-- Period Credit --}}
                            <td class="px-2 py-1.5 text-right tabular-nums">
                                @if(abs((float) $account->report_period_credit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_period_credit, 2) }}
                                @endif
                            </td>


                            {{-- Closing Debit --}}
                            <td class="border-l border-gray-300 px-2 py-1.5 text-right font-medium tabular-nums">
                                @if(abs((float) $account->report_closing_debit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_closing_debit, 2) }}
                                @endif
                            </td>


                            {{-- Closing Credit --}}
                            <td class="px-2 py-1.5 text-right font-medium tabular-nums">
                                @if(abs((float) $account->report_closing_credit) < 0.005)
                                    -
                                @else
                                    {{ number_format((float) $account->report_closing_credit, 2) }}
                                @endif
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-3 py-8 text-center text-gray-500"
                            >
                                No accounts found for this company.

                            </td>

                        </tr>

                    @endforelse

                </tbody>


                {{-- Totals --}}
                <tfoot>

                    <tr class="border-t-2 border-gray-900 font-bold">

                        <td
                            colspan="2"
                            class="border border-gray-700 px-2 py-2 text-right"
                        >
                            TOTAL
                        </td>


                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalOpeningDebit, 2) }}
                        </td>

                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalOpeningCredit, 2) }}
                        </td>

                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalPeriodDebit, 2) }}
                        </td>

                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalPeriodCredit, 2) }}
                        </td>

                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalClosingDebit, 2) }}
                        </td>

                        <td class="border border-gray-700 px-2 py-2 text-right tabular-nums">
                            {{ number_format($totalClosingCredit, 2) }}
                        </td>

                    </tr>

                </tfoot>

            </table>

        </div>


        {{-- ============================================================
             BALANCE CHECK
        ============================================================= --}}

        <div class="mt-6 border-t border-gray-300 pt-4">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-semibold text-gray-700">
                        Closing Trial Balance
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Debit and Credit balances must agree.
                    </p>

                </div>


                <div class="text-right">

                    <p class="text-sm font-semibold text-gray-800">

                        Difference:
                        {{ number_format(abs((float) $balanceDifference), 2) }}

                    </p>


                    @if($isBalanced)

                        <p class="mt-1 font-bold text-green-700">
                            ✓ Trial Balance Matched
                        </p>

                    @else

                        <p class="mt-1 font-bold text-red-700">
                            ✗ Trial Balance Not Matched
                        </p>

                    @endif

                </div>

            </div>

        </div>


        {{-- ============================================================
             SIGNATURE
        ============================================================= --}}

        <div class="mt-16 print:mt-12">

            <div class="grid grid-cols-4 gap-10">

                <div>

                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-xs text-gray-700">
                        Prepared By
                    </p>

                </div>


                <div>

                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-xs text-gray-700">
                        Checked By
                    </p>

                </div>


                <div>

                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-xs text-gray-700">
                        Authorized By
                    </p>

                </div>


                <div>

                    <div class="border-b border-gray-700"></div>

                    <p class="mt-2 text-xs text-gray-700">
                        Date
                    </p>

                </div>

            </div>

        </div>


        {{-- Footer --}}
        <footer class="mt-8 border-t border-gray-300 pt-3 text-center text-[10px] text-gray-500">

            <p>
                This Trial Balance is prepared from the accounting records of
                {{ $company->company_name }}.
            </p>

            <p class="mt-1">
                Report Date: {{ $reportDate }}
            </p>

        </footer>

    </div>

</div>


{{-- ================================================================
     PERIOD FIELD SCRIPT
================================================================= --}}

<script>

    function toggleTrialBalancePeriodFields() {

        const period = document.getElementById('period').value;

        const monthField =
            document.getElementById('month-field');

        const customFromField =
            document.getElementById('custom-from-field');

        const customToField =
            document.getElementById('custom-to-field');

        if (period === 'month') {

            monthField.classList.remove('hidden');

            customFromField.classList.add('hidden');

            customToField.classList.add('hidden');

        } else if (period === 'custom') {

            monthField.classList.add('hidden');

            customFromField.classList.remove('hidden');

            customToField.classList.remove('hidden');

        } else {

            monthField.classList.add('hidden');

            customFromField.classList.add('hidden');

            customToField.classList.add('hidden');

        }

    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {

            toggleTrialBalancePeriodFields();

        }
    );

</script>


{{-- ================================================================
     AUTO PRINT
================================================================= --}}

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

    .tabular-nums {
        font-variant-numeric: tabular-nums;
    }


    @media print {

        @page {
            size: A4 landscape;
            margin: 10mm;
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


        #trial-balance-statement {

            width: 100% !important;

            max-width: none !important;

            margin: 0 !important;

            padding: 0 !important;

        }


        table {

            width: 100% !important;

        }


        tr,
        td,
        th {

            break-inside: avoid;

            page-break-inside: avoid;

        }


        thead {

            display: table-header-group;

        }


        tfoot {

            display: table-footer-group;

        }


        header {

            break-after: avoid;

            page-break-after: avoid;

        }

    }

</style>

@endsection