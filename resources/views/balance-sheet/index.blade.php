@extends('layouts.app')

@section('title', 'Balance Sheet')
@section('page-title', 'Balance Sheet')
@section('page-subtitle', 'Statement of Financial Position')

@section('header')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-semibold text-2xl text-gray-800">
            Balance Sheet
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Statement of Financial Position
        </p>
    </div>

    @if($financialYears->isNotEmpty())
        <form
            method="GET"
            action="{{ route('balance-sheet.index') }}"
            class="flex items-center gap-2"
        >
            <label class="text-sm font-medium text-gray-600">
                Financial Year:
            </label>

            <select
                name="financial_year_id"
                onchange="this.form.submit()"
                class="text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                @foreach($financialYears as $fy)
                    <option
                        value="{{ $fy->id }}"
                        {{ $fy->id == $financialYearId ? 'selected' : '' }}
                    >
                        {{ $fy->name ?? ($fy->start_date . ' - ' . $fy->end_date) }}
                    </option>
                @endforeach
            </select>
        </form>
    @endif
</div>
@endsection


@section('content')

<div class="py-8">

    <div class="max-w-7xl mx-auto px-4">

        {{-- ========================================================= --}}
        {{-- REPORT HEADER --}}
        {{-- ========================================================= --}}

        <div class="bg-white rounded-lg shadow p-8 mb-6">

            <div class="text-center">

                <h1 class="text-2xl font-bold uppercase text-gray-900">
                    BALANCE SHEET
                </h1>

                @if($company)
                    <h2 class="mt-4 text-lg font-bold text-gray-800">
                        {{ $company->name }}
                    </h2>

                    @if(!empty($company->address))
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $company->address }}
                        </p>
                    @endif
                @endif

                <h3 class="mt-4 text-xl font-bold uppercase text-gray-900">
                    Balance Sheet
                </h3>

                <p class="mt-1 text-sm font-semibold text-gray-700">
                    As at {{ $reportDate }}
                </p>

                @if($selectedYear)

                    <p class="mt-1 text-xs text-gray-500">
                        Financial Year:
                        {{ $selectedYear->name
                            ?? $selectedYear->year_name
                            ?? ($selectedYear->start_date . ' - ' . $selectedYear->end_date)
                        }}
                    </p>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- BALANCE SHEET --}}
        {{-- ========================================================= --}}

        <div class="bg-white rounded-lg shadow p-6">


            {{-- ===================================================== --}}
            {{-- ASSETS --}}
            {{-- ===================================================== --}}

            <section>

                <div class="border-b-2 border-gray-800 pb-2 mb-4">

                    <h3 class="text-xl font-bold text-gray-900">
                        ASSETS
                    </h3>

                </div>


                {{-- NON-CURRENT ASSETS --}}

                <div class="mb-6">

                    <h4 class="text-base font-bold text-gray-700 mb-3">
                        Non-Current Assets
                    </h4>

                    <table class="w-full border-collapse text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="border px-3 py-2 text-left w-24">
                                    Code
                                </th>

                                <th class="border px-3 py-2 text-left">
                                    Account
                                </th>

                                <th class="border px-3 py-2 text-right w-40">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($nonCurrentAssets as $account)

                                @php
                                    $balance = $calculateBalance($account);
                                @endphp

                                <tr>

                                    <td class="border px-3 py-2 text-gray-500">
                                        {{ $account->account_code }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $account->account_name }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        {{ number_format($balance, 2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        class="border px-3 py-3 text-center text-gray-400"
                                    >
                                        No non-current assets
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>

                            <tr class="font-semibold bg-gray-50">

                                <td
                                    colspan="2"
                                    class="border px-3 py-2 text-right"
                                >
                                    Total Non-Current Assets
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ number_format($totalNonCurrentAssets, 2) }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                {{-- CURRENT ASSETS --}}

                <div class="mb-6">

                    <h4 class="text-base font-bold text-gray-700 mb-3">
                        Current Assets
                    </h4>

                    <table class="w-full border-collapse text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="border px-3 py-2 text-left w-24">
                                    Code
                                </th>

                                <th class="border px-3 py-2 text-left">
                                    Account
                                </th>

                                <th class="border px-3 py-2 text-right w-40">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($currentAssets as $account)

                                @php
                                    $balance = $calculateBalance($account);
                                @endphp

                                <tr>

                                    <td class="border px-3 py-2 text-gray-500">
                                        {{ $account->account_code }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $account->account_name }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        {{ number_format($balance, 2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        class="border px-3 py-3 text-center text-gray-400"
                                    >
                                        No current assets
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>

                            <tr class="font-semibold bg-gray-50">

                                <td
                                    colspan="2"
                                    class="border px-3 py-2 text-right"
                                >
                                    Total Current Assets
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ number_format($totalCurrentAssets, 2) }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                {{-- TOTAL ASSETS --}}

                <div class="flex justify-between items-center border-t-2 border-gray-800 pt-3 mb-8">

                    <span class="font-bold text-lg">
                        TOTAL ASSETS
                    </span>

                    <span class="font-bold text-lg">
                        {{ number_format($totalAssets, 2) }}
                    </span>

                </div>

            </section>



            {{-- ===================================================== --}}
            {{-- LIABILITIES --}}
            {{-- ===================================================== --}}

            <section class="mt-10">

                <div class="border-b-2 border-gray-800 pb-2 mb-4">

                    <h3 class="text-xl font-bold text-gray-900">
                        LIABILITIES
                    </h3>

                </div>


                {{-- NON-CURRENT LIABILITIES --}}

                <div class="mb-6">

                    <h4 class="text-base font-bold text-gray-700 mb-3">
                        Non-Current Liabilities
                    </h4>

                    <table class="w-full border-collapse text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="border px-3 py-2 text-left w-24">
                                    Code
                                </th>

                                <th class="border px-3 py-2 text-left">
                                    Account
                                </th>

                                <th class="border px-3 py-2 text-right w-40">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($nonCurrentLiabilities as $account)

                                @php
                                    $balance = $calculateBalance($account);
                                @endphp

                                <tr>

                                    <td class="border px-3 py-2 text-gray-500">
                                        {{ $account->account_code }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $account->account_name }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        {{ number_format($balance, 2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        class="border px-3 py-3 text-center text-gray-400"
                                    >
                                        No non-current liabilities
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>

                            <tr class="font-semibold bg-gray-50">

                                <td
                                    colspan="2"
                                    class="border px-3 py-2 text-right"
                                >
                                    Total Non-Current Liabilities
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ number_format($totalNonCurrentLiabilities, 2) }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                {{-- CURRENT LIABILITIES --}}

                <div class="mb-6">

                    <h4 class="text-base font-bold text-gray-700 mb-3">
                        Current Liabilities
                    </h4>

                    <table class="w-full border-collapse text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="border px-3 py-2 text-left w-24">
                                    Code
                                </th>

                                <th class="border px-3 py-2 text-left">
                                    Account
                                </th>

                                <th class="border px-3 py-2 text-right w-40">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($currentLiabilities as $account)

                                @php
                                    $balance = $calculateBalance($account);
                                @endphp

                                <tr>

                                    <td class="border px-3 py-2 text-gray-500">
                                        {{ $account->account_code }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $account->account_name }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        {{ number_format($balance, 2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        class="border px-3 py-3 text-center text-gray-400"
                                    >
                                        No current liabilities
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>

                            <tr class="font-semibold bg-gray-50">

                                <td
                                    colspan="2"
                                    class="border px-3 py-2 text-right"
                                >
                                    Total Current Liabilities
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ number_format($totalCurrentLiabilities, 2) }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                {{-- TOTAL LIABILITIES --}}

                <div class="flex justify-between items-center border-t-2 border-gray-800 pt-3 mb-8">

                    <span class="font-bold text-lg">
                        TOTAL LIABILITIES
                    </span>

                    <span class="font-bold text-lg">
                        {{ number_format($totalLiabilities, 2) }}
                    </span>

                </div>

            </section>



            {{-- ===================================================== --}}
            {{-- EQUITY --}}
            {{-- ===================================================== --}}

            <section class="mt-10">

                <div class="border-b-2 border-gray-800 pb-2 mb-4">

                    <h3 class="text-xl font-bold text-gray-900">
                        EQUITY
                    </h3>

                </div>


                <table class="w-full border-collapse text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="border px-3 py-2 text-left w-24">
                                Code
                            </th>

                            <th class="border px-3 py-2 text-left">
                                Account
                            </th>

                            <th class="border px-3 py-2 text-right w-40">
                                Amount
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($equity as $account)

                            @php
                                $balance = $calculateBalance($account);
                            @endphp

                            <tr>

                                <td class="border px-3 py-2 text-gray-500">
                                    {{ $account->account_code }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $account->account_name }}
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ number_format($balance, 2) }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="3"
                                    class="border px-3 py-3 text-center text-gray-400"
                                >
                                    No equity accounts
                                </td>

                            </tr>

                        @endforelse


                        {{-- CURRENT YEAR PROFIT / LOSS --}}

                        <tr
                            class="{{ $currentProfit >= 0
                                ? 'bg-green-50'
                                : 'bg-red-50'
                            }}"
                        >

                            <td class="border px-3 py-2 text-gray-400">
                                —
                            </td>

                            <td class="border px-3 py-2 font-medium">

                                Current Year
                                {{ $currentProfit >= 0 ? 'Profit' : 'Loss' }}

                            </td>

                            <td
                                class="border px-3 py-2 text-right font-semibold
                                {{ $currentProfit < 0
                                    ? 'text-red-600'
                                    : 'text-green-700'
                                }}"
                            >

                                {{ number_format($currentProfit, 2) }}

                            </td>

                        </tr>

                    </tbody>

                    <tfoot>

                        <tr class="font-bold bg-blue-50">

                            <td
                                colspan="2"
                                class="border px-3 py-3 text-right"
                            >
                                TOTAL EQUITY
                            </td>

                            <td class="border px-3 py-3 text-right">

                                {{ number_format(
                                    $totalEquityIncludingProfit,
                                    2
                                ) }}

                            </td>

                        </tr>

                    </tfoot>

                </table>

            </section>



            {{-- ===================================================== --}}
            {{-- TOTAL LIABILITIES + EQUITY --}}
            {{-- ===================================================== --}}

            <div class="mt-10 border-t-4 border-gray-900 pt-4">

                <div class="flex justify-between items-center">

                    <span class="font-bold text-xl">
                        TOTAL LIABILITIES + EQUITY
                    </span>

                    <span class="font-bold text-xl">
                        {{ number_format(
                            $totalLiabilitiesAndEquity,
                            2
                        ) }}
                    </span>

                </div>

            </div>



            {{-- ===================================================== --}}
            {{-- BALANCE CHECK --}}
            {{-- ===================================================== --}}

            <section class="mt-10">

                <div class="border-b-2 border-gray-800 pb-2 mb-4">

                    <h3 class="text-xl font-bold text-gray-900">
                        BALANCE CHECK
                    </h3>

                </div>


                <div class="space-y-3">

                    <div class="flex justify-between">

                        <span class="font-medium text-gray-700">
                            Total Assets
                        </span>

                        <span class="font-semibold">
                            {{ number_format($totalAssets, 2) }}
                        </span>

                    </div>


                    <div class="flex justify-between">

                        <span class="font-medium text-gray-700">
                            Total Liabilities + Equity
                        </span>

                        <span class="font-semibold">
                            {{ number_format(
                                $totalLiabilitiesAndEquity,
                                2
                            ) }}
                        </span>

                    </div>


                    <div class="flex justify-between border-t pt-3">

                        <span class="font-medium text-gray-700">
                            Difference
                        </span>

                        <span
                            class="font-bold
                            {{ $difference < 0.01
                                ? 'text-green-700'
                                : 'text-red-600'
                            }}"
                        >

                            {{ number_format($difference, 2) }}

                        </span>

                    </div>

                </div>


                {{-- BALANCED --}}

                @if($isBalanced)

                    <div
                        class="mt-6 p-4 bg-green-50 border border-green-300 rounded-lg text-green-700"
                    >

                        <div class="font-bold text-lg">
                            ✓ Balance Sheet Matched
                        </div>

                        <div class="text-sm mt-1">
                            Total Assets equals Total Liabilities + Equity.
                        </div>

                    </div>

                @else

                    <div
                        class="mt-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-700"
                    >

                        <div class="font-bold text-lg">
                            ✗ Balance Sheet Not Matched
                        </div>

                        <div class="text-sm mt-1">
                            Difference:
                            {{ number_format($difference, 2) }}
                        </div>

                    </div>

                @endif

            </section>


        </div>

    </div>

</div>

@endsection