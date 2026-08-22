<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        {{ $reportTitle }}
    </title>

    <style>
        @page {
            size: A4 portrait;
            margin: 35px 45px 45px 45px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .statement {
            width: 100%;
        }

        .header {
            text-align: center;
            border-bottom: 1.5px solid #111827;
            padding-bottom: 14px;
        }

        .logo {
            max-height: 60px;
            max-width: 140px;
            margin-bottom: 8px;
        }

        .company-name {
            margin: 0;
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-address {
            margin-top: 4px;
            font-size: 9px;
            color: #4b5563;
        }

        .report-title {
            margin-top: 16px;
            margin-bottom: 4px;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .period {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        thead th {
            border-bottom: 1.5px solid #111827;
            padding: 7px 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: none;
        }

        th:first-child {
            text-align: left;
        }

        th:last-child {
            text-align: right;
        }

        td {
            padding: 5px 6px;
            vertical-align: middle;
        }

        .section-title td {
            padding-top: 15px;
            padding-bottom: 5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .account-name {
            padding-left: 22px;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .account-row td {
            border-bottom: 0.4px solid #e5e7eb;
        }

        .total-row td {
            border-top: 1px solid #111827;
            padding-top: 7px;
            padding-bottom: 7px;
            font-weight: bold;
        }

        .net-result {
            margin-top: 20px;
            border-top: 1.5px solid #111827;
            border-bottom: 1.5px solid #111827;
            padding: 9px 6px;
        }

        .net-result-table {
            width: 100%;
            margin: 0;
        }

        .net-label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .net-amount {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }

        .signatures {
            width: 100%;
            margin-top: 85px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .signature-table td {
            width: 50%;
            padding: 0 25px 0 0;
            border: none;
        }

        .signature-table td.right {
            padding-left: 25px;
            padding-right: 0;
        }

        .signature-line {
            height: 25px;
            border-bottom: 0.8px solid #374151;
        }

        .signature-label {
            margin-top: 5px;
            font-size: 9px;
            color: #374151;
        }

        .footer {
            margin-top: 35px;
            padding-top: 8px;
            border-top: 0.5px solid #d1d5db;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

<div class="statement">

    {{-- HEADER --}}
    <div class="header">

        @if(!empty($company->logo))
            @php
                $logoPath = public_path(
                    'storage/' . ltrim($company->logo, '/')
                );
            @endphp

            @if(file_exists($logoPath))
                <img
                    src="{{ $logoPath }}"
                    alt="{{ $company->company_name }}"
                    class="logo"
                >
            @endif
        @endif

        <h1 class="company-name">
            {{ $company->company_name }}
        </h1>

        @if($company->address || $company->city)
            <div class="company-address">
                {{ $company->address }}

                @if($company->address && $company->city)
                    ,
                @endif

                {{ $company->city }}
            </div>
        @endif

        <div class="report-title">
            {{ $reportTitle }}
        </div>

        <div class="period">
            {{ $periodText }}
        </div>

    </div>


    {{-- MAIN STATEMENT --}}
    <table>

        <thead>
            <tr>
                <th style="width: 75%;">
                    Particulars
                </th>

                <th style="width: 25%;">
                    Amount ({{ $currencyCode }})
                </th>
            </tr>
        </thead>

        <tbody>

            {{-- REVENUE --}}
            <tr class="section-title">
                <td colspan="2">
                    Revenue / Income
                </td>
            </tr>

            @forelse($incomeAccounts as $account)

                <tr class="account-row">

                    <td class="account-name">
                        {{ $account->account_name }}
                    </td>

                    <td class="amount">

                        @if(abs((float) $account->report_amount) < 0.005)
                            -
                        @else
                            {{ number_format((float) $account->report_amount, 2) }}
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="2">
                        No income accounts found.
                    </td>
                </tr>

            @endforelse

            <tr class="total-row">

                <td>
                    TOTAL REVENUE
                </td>

                <td class="amount">
                    {{ number_format((float) $totalIncome, 2) }}
                </td>

            </tr>


            {{-- EXPENSES --}}
            <tr class="section-title">
                <td colspan="2">
                    Expenses
                </td>
            </tr>

            @forelse($expenseAccounts as $account)

                <tr class="account-row">

                    <td class="account-name">
                        {{ $account->account_name }}
                    </td>

                    <td class="amount">

                        @if(abs((float) $account->report_amount) < 0.005)
                            -
                        @else
                            {{ number_format((float) $account->report_amount, 2) }}
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="2">
                        No expense accounts found.
                    </td>
                </tr>

            @endforelse

            <tr class="total-row">

                <td>
                    TOTAL EXPENSES
                </td>

                <td class="amount">
                    {{ number_format((float) $totalExpense, 2) }}
                </td>

            </tr>

        </tbody>

    </table>


    {{-- NET PROFIT / LOSS --}}
    <div class="net-result">

        <table class="net-result-table">

            <tr>

                <td class="net-label">

                    @if($netProfit > 0)
                        NET PROFIT
                    @elseif($netLoss > 0)
                        NET LOSS
                    @else
                        NET PROFIT / LOSS
                    @endif

                </td>

                <td class="net-amount">

                    {{ number_format(abs((float) $netResult), 2) }}

                </td>

            </tr>

        </table>

    </div>


    {{-- SIGNATURES --}}
    <div class="signatures">

        <table class="signature-table">

            <tr>

                <td>
                    <div class="signature-line"></div>

                    <div class="signature-label">
                        Prepared By
                    </div>
                </td>

                <td class="right">
                    <div class="signature-line"></div>

                    <div class="signature-label">
                        Checked By
                    </div>
                </td>

            </tr>

            <tr>

                <td style="padding-top: 45px;">
                    <div class="signature-line"></div>

                    <div class="signature-label">
                        Authorized By
                    </div>
                </td>

                <td
                    class="right"
                    style="padding-top: 45px;"
                >
                    <div class="signature-line"></div>

                    <div class="signature-label">
                        Date
                    </div>
                </td>

            </tr>

        </table>

    </div>


    {{-- FOOTER --}}
    <div class="footer">

        This statement is prepared from the accounting records of
        {{ $company->company_name }}.

        Financial Year:
        {{ $financialYear->year_name }}

    </div>

</div>

</body>
</html>