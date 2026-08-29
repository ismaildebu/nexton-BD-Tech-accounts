<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — {{ $publication->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 14px; font-weight: bold; }
        .sub { font-size: 9px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1f2937; color: #fff; padding: 5px 4px; font-size: 8px; border: 1px solid #1f2937; }
        td { padding: 4px; border: 1px solid #d1d5db; }
        .num { text-align: right; }
        .in { color: #15803d; }
        .out { color: #b91c1c; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .footer { margin-top: 10px; font-size: 7px; color: #9ca3af; }
        .badge { padding: 1px 5px; border-radius: 4px; font-size: 7px; }
        .badge-dist { background: #fee2e2; color: #b91c1c; }
        .badge-in { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none"><tr>
            <td style="border:none; width:60%;">
                <div class="title">Newspaper Stock Report</div>
                <div class="sub">Publication: {{ $publication->name }} ({{ $publication->code }})</div>
                @if(request('from_date') || request('to_date'))
                    <div class="sub">Period: {{ request('from_date', 'All') }} to {{ request('to_date', 'All') }}</div>
                @endif
            </td>
            <td style="border:none; text-align:right;">
                <div class="sub">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                <div style="font-size:11px; font-weight:bold; margin-top:4px;">
                    Closing Balance: {{ number_format($runningBalance) }}
                </div>
            </td>
        </tr></table>
    </div>

    @if($openingBalance)
    <div style="margin-bottom:6px; font-size:9px;">Opening Balance: <strong>{{ number_format($openingBalance) }}</strong></div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th class="num">In</th>
                <th class="num">Out</th>
                <th class="num">Balance</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $i => $mov)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $mov->movement_date?->format('d M Y') }}</td>
                <td><span class="badge {{ in_array($mov->type, ['distribution','damage']) ? 'badge-dist' : 'badge-in' }}">{{ ucfirst($mov->type) }}</span></td>
                <td class="num in">{{ $mov->quantity > 0 ? number_format($mov->quantity) : '' }}</td>
                <td class="num out">{{ $mov->quantity < 0 ? number_format(abs($mov->quantity)) : '' }}</td>
                <td class="num" style="{{ $mov->running_balance < 0 ? 'color:#b91c1c;' : '' }}">{{ number_format($mov->running_balance) }}</td>
                <td style="font-size:8px;">{{ $mov->notes }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; padding:8px;">No movements.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Total</td>
                <td class="num in">{{ number_format($movements->where('quantity', '>', 0)->sum('quantity')) }}</td>
                <td class="num out">{{ number_format(abs($movements->where('quantity', '<', 0)->sum('quantity'))) }}</td>
                <td class="num">{{ number_format($runningBalance) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">{{ $publication->name }} Stock Report &middot; {{ now()->format('d M Y') }}</div>
</body>
</html>
