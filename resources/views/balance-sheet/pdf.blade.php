<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Balance Sheet</title>

    <style>
        @page {
            margin: 30px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .company-address {
            font-size: 10px;
            color: #555;
            margin-bottom: 10px;
        }

        .report-title {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .report-date {
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }

        .financial-year {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            border-bottom: 2px solid #222;
            padding-bottom: 5px;
            margin-top: 22px;
            margin-bottom: 8px;
        }

        .sub-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th {
            background: #f3f4f6;
            border: 1px solid #999;
            padding: 6px;
            font-weight: bold;
        }

        td {
            border: 1px solid #aaa;
            padding: 6px;
        }

        .code {
            width: 70px;
            color: #666;
        }

        .amount {
            width: 120px;
        }

        .total-row td {
            font-weight: bold;
            background: #f8f8f8;
        }

        .grand-total {
            border-top: 2px solid #222;
            border-bottom: 2px solid #222;
            padding: 8px 0;
            margin-top: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            font-weight: bold;
        }

        .equity-profit {
            background: #f0fdf4;
        }

        .equity-loss {
            background: #fef2f2;
        }

        .profit {
            color: #15803d;
            font-weight: bold;
        }

        .loss {
            color: #dc2626;
            font-weight: bold;
        }

        .balance-check {
            margin-top: 25px;
            border-top: 2px solid #222;
            padding-top: 10px;
        }

        .status {
            margin-top: 12px;
            padding: 10px;
            border: 1px solid #999;
        }

        .status-balanced {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }

        .status-unbalanced {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #aaa;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

{{-- ========================================================= --}}
{{-- REPORT HEADER --}}
{{-- ========================================================= --}}

<div class="text-center">

    @if($company ?? null)

        <div class="company-name">
            {{ $company->name }}
        </div>

        @if(!empty($company->address))
            <div class="company-address">
                {{ $company->address }}
            </div>
        @endif

    @endif

    <div class="report-title">
        Balance Sheet
    </div>

    <div class="report-date">
        As at {{ $reportDate }}
    </div>

    @if($selectedYear ?? null)

        <div class="financial-year">

            Financial Year:

            {{ $selectedYear->name
                ?? $selectedYear->year_name
                ?? ($selectedYear->start_date . ' - ' . $selectedYear->end_date)
            }}

        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- ASSETS --}}
{{-- ========================================================= --}}

<div class="section-title">
    ASSETS
</div>


{{-- NON-CURRENT ASSETS --}}

<div class="sub-title">
    Non-Current Assets
</div>

<table>

    <thead>
        <tr>
            <th class="code text-left">
                Code
            </th>

            <th class="text-left">
                Account
            </th>

            <th class="amount text-right">
                Amount
            </th>
        </tr>
    </thead>

    <tbody>

        @forelse($nonCurrentAssets ?? [] as $account)

            @php
                $balance = $calculateBalance($account);
            @endphp

            <tr>

                <td class="code">
                    {{ $account->account_code }}
                </td>

                <td>
                    {{ $account->account_name }}
                </td>

                <td class="amount text-right">
                    {{ number_format($balance, 2) }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="3" class="text-center">
                    No non-current assets
                </td>
            </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr class="total-row">

            <td colspan="2" class="text-right">
                Total Non-Current Assets
            </td>

            <td class="text-right">
                {{ number_format($totalNonCurrentAssets ?? 0, 2) }}
            </td>

        </tr>

    </tfoot>

</table>


{{-- CURRENT ASSETS --}}

<div class="sub-title">
    Current Assets
</div>

<table>

    <thead>
        <tr>
            <th class="code text-left">
                Code
            </th>

            <th class="text-left">
                Account
            </th>

            <th class="amount text-right">
                Amount
            </th>
        </tr>
    </thead>

    <tbody>

        @forelse($currentAssets ?? [] as $account)

            @php
                $balance = $calculateBalance($account);
            @endphp

            <tr>

                <td class="code">
                    {{ $account->account_code }}
                </td>

                <td>
                    {{ $account->account_name }}
                </td>

                <td class="amount text-right">
                    {{ number_format($balance, 2) }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="3" class="text-center">
                    No current assets
                </td>
            </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr class="total-row">

            <td colspan="2" class="text-right">
                Total Current Assets
            </td>

            <td class="text-right">
                {{ number_format($totalCurrentAssets ?? 0, 2) }}
            </td>

        </tr>

    </tfoot>

</table>


{{-- TOTAL ASSETS --}}

<div class="grand-total">

    <table style="margin: 0;">

        <tr>

            <td style="border: 0; padding: 0;">
                TOTAL ASSETS
            </td>

            <td
                style="border: 0; padding: 0;"
                class="text-right"
            >
                {{ number_format($totalAssets ?? 0, 2) }}
            </td>

        </tr>

    </table>

</div>



{{-- ========================================================= --}}
{{-- LIABILITIES --}}
{{-- ========================================================= --}}

<div class="section-title">
    LIABILITIES
</div>


{{-- NON-CURRENT LIABILITIES --}}

<div class="sub-title">
    Non-Current Liabilities
</div>

<table>

    <thead>

        <tr>

            <th class="code text-left">
                Code
            </th>

            <th class="text-left">
                Account
            </th>

            <th class="amount text-right">
                Amount
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse($nonCurrentLiabilities ?? [] as $account)

            @php
                $balance = $calculateBalance($account);
            @endphp

            <tr>

                <td class="code">
                    {{ $account->account_code }}
                </td>

                <td>
                    {{ $account->account_name }}
                </td>

                <td class="amount text-right">
                    {{ number_format($balance, 2) }}
                </td>

            </tr>

        @empty

            <tr>

                <td colspan="3" class="text-center">
                    No non-current liabilities
                </td>

            </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr class="total-row">

            <td colspan="2" class="text-right">
                Total Non-Current Liabilities
            </td>

            <td class="text-right">
                {{ number_format($totalNonCurrentLiabilities ?? 0, 2) }}
            </td>

        </tr>

    </tfoot>

</table>


{{-- CURRENT LIABILITIES --}}

<div class="sub-title">
    Current Liabilities
</div>

<table>

    <thead>

        <tr>

            <th class="code text-left">
                Code
            </th>

            <th class="text-left">
                Account
            </th>

            <th class="amount text-right">
                Amount
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse($currentLiabilities ?? [] as $account)

            @php
                $balance = $calculateBalance($account);
            @endphp

            <tr>

                <td class="code">
                    {{ $account->account_code }}
                </td>

                <td>
                    {{ $account->account_name }}
                </td>

                <td class="amount text-right">
                    {{ number_format($balance, 2) }}
                </td>

            </tr>

        @empty

            <tr>

                <td colspan="3" class="text-center">
                    No current liabilities
                </td>

            </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr class="total-row">

            <td colspan="2" class="text-right">
                Total Current Liabilities
            </td>

            <td class="text-right">
                {{ number_format($totalCurrentLiabilities ?? 0, 2) }}
            </td>

        </tr>

    </tfoot>

</table>


{{-- TOTAL LIABILITIES --}}

<div class="grand-total">

    <table style="margin: 0;">

        <tr>

            <td style="border: 0; padding: 0;">
                TOTAL LIABILITIES
            </td>

            <td
                style="border: 0; padding: 0;"
                class="text-right"
            >
                {{ number_format($totalLiabilities ?? 0, 2) }}
            </td>

        </tr>

    </table>

</div>



{{-- ========================================================= --}}
{{-- EQUITY --}}
{{-- ========================================================= --}}

<div class="section-title">
    EQUITY
</div>

<table>

    <thead>

        <tr>

            <th class="code text-left">
                Code
            </th>

            <th class="text-left">
                Account
            </th>

            <th class="amount text-right">
                Amount
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse($equity ?? [] as $account)

            @php
                $balance = $calculateBalance($account);
            @endphp

            <tr>

                <td class="code">
                    {{ $account->account_code }}
                </td>

                <td>
                    {{ $account->account_name }}
                </td>

                <td class="amount text-right">
                    {{ number_format($balance, 2) }}
                </td>

            </tr>

        @empty

            <tr>

                <td colspan="3" class="text-center">
                    No equity accounts
                </td>

            </tr>

        @endforelse


        {{-- CURRENT YEAR PROFIT / LOSS --}}

        <tr
            class="{{ ($currentProfit ?? 0) >= 0
                ? 'equity-profit'
                : 'equity-loss'
            }}"
        >

            <td class="code">
                -
            </td>

            <td>

                Current Year
                {{ ($currentProfit ?? 0) >= 0 ? 'Profit' : 'Loss' }}

            </td>

            <td
                class="amount text-right
                {{ ($currentProfit ?? 0) < 0
                    ? 'loss'
                    : 'profit'
                }}"
            >

                {{ number_format($currentProfit ?? 0, 2) }}

            </td>

        </tr>

    </tbody>

    <tfoot>

        <tr class="total-row">

            <td colspan="2" class="text-right">
                TOTAL EQUITY
            </td>

            <td class="text-right">

                {{ number_format(
                    $totalEquityIncludingCurrentYear
                        ?? $totalEquityIncludingProfit
                        ?? 0,
                    2
                ) }}

            </td>

        </tr>

    </tfoot>

</table>



{{-- ========================================================= --}}
{{-- TOTAL LIABILITIES + EQUITY --}}
{{-- ========================================================= --}}

<div class="grand-total">

    <table style="margin: 0;">

        <tr>

            <td style="border: 0; padding: 0;">
                TOTAL LIABILITIES + EQUITY
            </td>

            <td
                style="border: 0; padding: 0;"
                class="text-right"
            >

                {{ number_format(
                    $totalLiabilitiesAndEquity ?? 0,
                    2
                ) }}

            </td>

        </tr>

    </table>

</div>



{{-- ========================================================= --}}
{{-- BALANCE CHECK --}}
{{-- ========================================================= --}}

<div class="balance-check">

    <div class="section-title" style="margin-top: 0;">
        BALANCE CHECK
    </div>

    <table>

        <tr>

            <td>
                Total Assets
            </td>

            <td class="text-right">
                {{ number_format($totalAssets ?? 0, 2) }}
            </td>

        </tr>

        <tr>

            <td>
                Total Liabilities + Equity
            </td>

            <td class="text-right">

                {{ number_format(
                    $totalLiabilitiesAndEquity ?? 0,
                    2
                ) }}

            </td>

        </tr>

        <tr class="total-row">

            <td>
                Difference
            </td>

            <td class="text-right">

                {{ number_format($difference ?? 0, 2) }}

            </td>

        </tr>

    </table>


    @if($isBalanced ?? false)

        <div class="status status-balanced">

            <strong>
                ✓ Balance Sheet Matched
            </strong>

            <br>

            Total Assets equals Total Liabilities + Equity.

        </div>

    @else

        <div class="status status-unbalanced">

            <strong>
                ✕ Balance Sheet Not Matched
            </strong>

            <br>

            Difference:
            {{ number_format($difference ?? 0, 2) }}

        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- FOOTER --}}
{{-- ========================================================= --}}

<div class="footer">

    Balance Sheet Report

    @if($company ?? null)
        — {{ $company->name }}
    @endif

    — Generated on {{ now()->format('d F Y h:i A') }}

</div>

</body>
</html>