<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 14px; font-weight: bold; }
        .sub { font-size: 9px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1f2937; color: #fff; padding: 5px 4px; font-size: 8px; border: 1px solid #1f2937; }
        td { padding: 4px; border: 1px solid #d1d5db; font-size: 9px; }
        .num { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .footer { margin-top: 10px; font-size: 7px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none"><tr>
            <td style="border:none; width:60%;">
                <div class="title">Distribution Summary Report</div>
                <div class="sub">{{ $company?->company_name }}</div>
                @if(request('from_date') || request('to_date'))
                    <div class="sub">Period: {{ request('from_date', 'All') }} — {{ request('to_date', 'All') }}</div>
                @endif
            </td>
            <td style="border:none; text-align:right;">
                <div class="sub">Generated: {{ now()->format('d M Y, h:i A') }}</div>
            </td>
        </tr></table>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Publication</th>
                <th class="num">Paid</th>
                <th class="num">Free</th>
                <th class="num">Total</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($distributions as $i => $dist)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $dist->distribution_date?->format('d M Y') }}</td>
                <td>{{ $dist->publication?->name }}</td>
                <td class="num">{{ number_format($dist->total_paid_quantity) }}</td>
                <td class="num">{{ number_format($dist->total_free_quantity) }}</td>
                <td class="num">{{ number_format($dist->total_quantity) }}</td>
                <td class="num">{{ number_format($dist->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;">No records.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Totals ({{ $distributions->count() }} runs)</td>
                <td class="num">{{ number_format($totals['paid']) }}</td>
                <td class="num">{{ number_format($totals['free']) }}</td>
                <td class="num">{{ number_format($totals['total']) }}</td>
                <td class="num">{{ number_format($totals['amount'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">{{ $company?->company_name }} &middot; Distribution Summary &middot; {{ now()->format('d M Y') }}</div>
</body>
</html>
