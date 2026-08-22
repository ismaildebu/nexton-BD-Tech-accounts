<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        {{ $reportTitle }}
    </title>

    <style>

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .company-name {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-title {
            margin-top: 8px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .period {
            margin-top: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .financial-year {
            margin-top: 3px;
            font-size: 8px;
            color: #4b5563;
        }

        .header {
            padding-bottom: 10px;
            border-bottom: 1.5px solid #111827;
        }

        .logo {
            max-height: 55px;
            max-width: 130px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 0.5px solid #6b7280;
            padding: 5px 6px;
        }

        th {
            font-weight: bold;
            background: #f3f4f6;
            text-align: center;
        }

        .account-name {
            text-align: left;
        }

        .code {
            color: #4b5563;
            width: 9%;
        }

        .account {
            width: 27%;
        }

        .amount {
            width: 10.66%;
            text-align: right;
        }

        .total-row td {
            border-top: 1.5px solid #111827;
            font-weight: bold;
            background: #f9fafb;
        }

        .balance-check {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 0.5px solid #d1d5db;
        }

        .matched {
            font-weight: bold;
            color: #166534;
        }

        .not-matched {
            font-weight: bold;
            color: #b91c1c;
        }

        .signatures {
            margin-top: 45px;
            width: 100%;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .signature-table td {
            border: none;
            width: 25%;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 0.5px solid #374151;
            padding-top: 5px;
            margin-top: 25px;
            text-align: left;
            color: #374151;
        }

        .footer {
            margin-top: 20px;
            padding-top: 6px;
            border-top: 0.5px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 7px;
        }

        .zero {
            color: #6b7280;
        }

    </style>

</head>


<body>

    {{-- ============================================================
         HEADER
    ============================================================= --}}

    <div class="header text-center">

        @if(!empty($company->logo))

            @php
                $logoPath = storage_path(
                    'app/public/' . ltrim($company->logo, '/')
                );
            @endphp

            @if(is_file($logoPath))

                <img
                    src="{{ $logoPath }}"
                    alt="{{ $company->company_name }}"
                    class="logo"
                >

            @endif

        @endif


        <div class="company-name">
            {{ $company->company_name }}
        </div>


        @if($company->address || $company->city)

            <div style="margin-top: 3px; font-size: 8px; color: #4b5563;">

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


        <div class="financial-year">
            Financial Year: {{ $financialYear->year_name }}
        </div>

    </div>


    {{-- ============================================================
         TRIAL BALANCE TABLE
    ============================================================= --}}

    <table>

        <thead>

            <tr>

                <th rowspan="2" class="code">
                    Code
                </th>

                <th rowspan="2" class="account">
                    Account
                </th>

                <th colspan="2">
                    Opening Balance
                </th>

                <th colspan="2">
                    Period Movement
                </th>

                <th colspan="2">
                    Closing Balance
                </th>

            </tr>


            <tr>

                <th class="amount">
                    Debit
                </th>

                <th class="amount">
                    Credit
                </th>

                <th class="amount">
                    Debit
                </th>

                <th class="amount">
                    Credit
                </th>

                <th class="amount">
                    Debit
                </th>

                <th class="amount">
                    Credit
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($accounts as $account)

                <tr>

                    <td class="code">
                        {{ $account->account_code }}
                    </td>

                    <td class="account account-name">
                        {{ $account->account_name }}
                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_opening_debit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_opening_debit, 2) }}

                        @endif

                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_opening_credit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_opening_credit, 2) }}

                        @endif

                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_period_debit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_period_debit, 2) }}

                        @endif

                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_period_credit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_period_credit, 2) }}

                        @endif

                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_closing_debit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_closing_debit, 2) }}

                        @endif

                    </td>


                    <td class="amount">

                        @if(abs((float) $account->report_closing_credit) < 0.005)

                            <span class="zero">-</span>

                        @else

                            {{ number_format((float) $account->report_closing_credit, 2) }}

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="8"
                        style="text-align: center; padding: 15px;"
                    >
                        No accounts found.

                    </td>

                </tr>

            @endforelse

        </tbody>


        <tfoot>

            <tr class="total-row">

                <td
                    colspan="2"
                    class="text-right"
                >
                    TOTAL
                </td>

                <td class="amount">
                    {{ number_format($totalOpeningDebit, 2) }}
                </td>

                <td class="amount">
                    {{ number_format($totalOpeningCredit, 2) }}
                </td>

                <td class="amount">
                    {{ number_format($totalPeriodDebit, 2) }}
                </td>

                <td class="amount">
                    {{ number_format($totalPeriodCredit, 2) }}
                </td>

                <td class="amount">
                    {{ number_format($totalClosingDebit, 2) }}
                </td>

                <td class="amount">
                    {{ number_format($totalClosingCredit, 2) }}
                </td>

            </tr>

        </tfoot>

    </table>


    {{-- ============================================================
         BALANCE CHECK
    ============================================================= --}}

    <div class="balance-check">

        <table style="border: none; margin: 0;">

            <tr>

                <td style="border: none; padding: 2px 0;">

                    Closing Debit:
                    <strong>
                        {{ number_format($totalClosingDebit, 2) }}
                    </strong>

                </td>


                <td
                    style="border: none; padding: 2px 0;"
                    class="text-right"
                >

                    Closing Credit:
                    <strong>
                        {{ number_format($totalClosingCredit, 2) }}
                    </strong>

                </td>

            </tr>


            <tr>

                <td
                    colspan="2"
                    style="border: none; padding: 5px 0;"
                    class="{{ $isBalanced ? 'matched' : 'not-matched' }}"
                >

                    @if($isBalanced)

                        ✓ Trial Balance Matched

                    @else

                        ✗ Trial Balance Not Matched
                        —
                        Difference:
                        {{ number_format(abs($balanceDifference), 2) }}

                    @endif

                </td>

            </tr>

        </table>

    </div>


    {{-- ============================================================
         SIGNATURES
    ============================================================= --}}

    <div class="signatures">

        <table class="signature-table">

            <tr>

                <td>

                    <div class="signature-line">
                        Prepared By
                    </div>

                </td>


                <td>

                    <div class="signature-line">
                        Checked By
                    </div>

                </td>


                <td>

                    <div class="signature-line">
                        Authorized By
                    </div>

                </td>


                <td>

                    <div class="signature-line">
                        Date
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ============================================================
         FOOTER
    ============================================================= --}}

    <div class="footer">

        <div>
            This Trial Balance is prepared from the accounting records of
            {{ $company->company_name }}.
        </div>

        <div style="margin-top: 3px;">
            Report Date: {{ $reportDate }}
        </div>

    </div>

</body>

</html>