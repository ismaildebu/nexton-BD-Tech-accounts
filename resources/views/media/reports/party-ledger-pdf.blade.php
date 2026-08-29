<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Party Ledger — {{ $party->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 10px; }
        .title { font-size: 14px; font-weight: bold; }
        .sub { font-size: 9px; color: #6b7280; }
        .summary { margin-bottom: 10px; }
        .summary table { border: none; }
        .summary td { border: none; padding: 3px 6px; }
        .summary .label { color: #6b7280; font-size: 8px; text-transform: uppercase; }
        .summary .val { font-size: 12px; font-weight: bold; }
        table.main { width: 100%; border-collapse: collapse; }
        table.main th { background: #1f2937; color: #fff; padding: 5px 4px; font-size: 8px; border: 1px solid #1f2937; }
        table.main td { padding: 4px; border: 1px solid #d1d5db; font-size: 8px; }
        .num { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .type-dist { background: #dbeafe; color: #1d4ed8; padding: 1px 4px; border-radius: 3px; font-size: 7px; }
        .type-ret  { background: #ffedd5; color: #c2410c; padding: 1px 4px; border-radius: 3px; font-size: 7px; }
        .type-coll { background: #dcfce7; color: #15803d; padding: 1px 4px; border-radius: 3px; font-size: 7px; }
        .footer { margin-top: 10px; font-size: 7px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width:100%;"><tr>
            <td style="border:none; width:55%;">
                <div class="title">Party Ledger</div>
                <div class="sub">{{ $company?->company_name }}</div>
                <div class="sub">Party: <strong>{{ $party->name }}</strong> ({{ ucfirst($party->type) }})</div>
                @if($party->phone)<div class="sub">Phone: {{ $party->phone }}</div>@endif
                @if(request('from_date') || request('to_date'))
                    <div class="sub">Period: {{ request('from_date', 'All') }} — {{ request('to_date', 'All') }}</div>
                @endif
            </td>
            <td style="border:none; text-align:right; vertical-align:top;">
                <div class="sub">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                <div style="margin-top:6px;">
                    <div class="sub">Sales Amount</div>
                    <div style="font-size:13px; font-weight:bold;">{{ number_format($totals['amount'], 2) }}</div>
                </div>
                <div style="margin-top:4px;">
                    <div class="sub">Outstanding</div>
                    <div style="font-size:13px; font-weight:bold; color:{{ $totals['balance'] > 0 ? '#b91c1c' : '#15803d' }};">
                        {{ number_format($totals['balance'], 2) }}
                    </div>
                </div>
            </td>
        </tr></table>
    </div>

    <table class="main">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Publication</th>
                <th class="num">Copies</th>
                <th class="num">Returned</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th>Ref</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerLines as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ is_string($line->date) ? $line->date : $line->date?->format('d M Y') }}</td>
                <td>
                    <span class="{{ $line->type === 'Distribution' ? 'type-dist' : ($line->type === 'Return' ? 'type-ret' : 'type-coll') }}">
                        {{ $line->type }}
                    </span>
                </td>
                <td>{{ $line->publication }}</td>
                <td class="num">{{ $line->total > 0 ? number_format($line->total) : '' }}</td>
                <td class="num">{{ $line->returned > 0 ? number_format($line->returned) : '' }}</td>
                <td class="num">{{ $line->dr_amount > 0 ? number_format($line->dr_amount, 2) : '' }}</td>
                <td class="num">{{ $line->cr_amount > 0 ? number_format($line->cr_amount, 2) : '' }}</td>
                <td style="font-size:7px;">{{ $line->ref }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center; padding:8px;">No records.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">Totals</td>
                <td class="num">{{ number_format($totals['distributed']) }}</td>
                <td class="num">{{ number_format($totals['returned']) }}</td>
                <td class="num">{{ number_format($totals['amount'], 2) }}</td>
                <td class="num">{{ number_format($totals['collected'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">{{ $party->name }} &middot; Party Ledger &middot; {{ now()->format('d M Y') }}</div>
</body>
</html>
