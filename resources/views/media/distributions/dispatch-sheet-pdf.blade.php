<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispatch Sheet - {{ $distribution->publication?->name }} - {{ $distribution->distribution_date?->format('Y-m-d') }}</title>
    <style>
        /* dompdf: table/block layout only, no flexbox/grid — matches
           resources/views/media/print-orders/pdf.blade.php. */
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; margin: 0; }
        .header-table { width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 10px; margin-bottom: 14px; }
        .company-name { font-size: 16px; font-weight: bold; color: #111827; }
        .company-meta { font-size: 9px; color: #6b7280; }
        .doc-box { border: 2px solid #1f2937; padding: 6px 12px; text-align: center; }
        .doc-label { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #111827; }

        .meta-table { width: 100%; margin-bottom: 12px; }
        .meta-table td { padding: 2px 0; font-size: 9px; }
        .meta-label { color: #6b7280; width: 110px; }
        .meta-value { color: #111827; font-weight: bold; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th {
            background: #1f2937; color: #ffffff; padding: 6px 4px;
            font-size: 9px; text-align: center; border: 1px solid #1f2937;
        }
        table.items td { padding: 5px 4px; font-size: 9px; border: 1px solid #d1d5db; }
        table.items td.num { text-align: right; }
        table.items td.center { text-align: center; }
        table.items tbody tr:nth-child(even) { background: #f9fafb; }

        .type-badge {
            display: inline-block; padding: 1px 6px; border-radius: 8px;
            font-size: 8px; font-weight: bold; text-transform: uppercase;
        }
        .type-agent { background: #e0e7ff; color: #3730a3; }
        .type-hawker { background: #fef3c7; color: #92400e; }

        tfoot td { font-weight: bold; background: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 4px; }

        .pdf-footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $distribution->company?->company_name ?? config('app.name') }}</div>
                @if($distribution->company?->address)
                    <div class="company-meta">{{ $distribution->company->address }}</div>
                @endif
                @if($distribution->company?->phone)
                    <div class="company-meta">Tel: {{ $distribution->company->phone }}</div>
                @endif
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="doc-box">
                    <div class="doc-label">Distribution Dispatch Sheet</div>
                    <div class="doc-title">{{ $distribution->publication?->name }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 33%;"><span class="meta-label">Distribution Date</span></td>
            <td style="width: 33%;"><span class="meta-value">{{ $distribution->distribution_date?->format('d F Y') }}</span></td>
            <td style="width: 34%;"><span class="meta-label">Status</span> <span class="meta-value">{{ $distribution->status }}</span></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Party Name</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 25%;">Address</th>
                <th style="width: 12%;">Phone</th>
                <th style="width: 10%;">Paid</th>
                <th style="width: 10%;">Free</th>
                <th style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($distribution->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->party?->name ?? '—' }}</td>
                    <td class="center">
                        <span class="type-badge {{ $item->party?->isAgent() ? 'type-agent' : 'type-hawker' }}">
                            {{ $item->party?->type ?? '—' }}
                        </span>
                    </td>
                    <td>
                        {{ $item->party?->address ?? '—' }}
                        @if($item->party?->area)
                            <br><span style="color:#6b7280;">{{ $item->party->area }}</span>
                        @endif
                    </td>
                    <td>{{ $item->party?->phone ?? '—' }}</td>
                    <td class="num">{{ number_format($item->paid_quantity) }}</td>
                    <td class="num">{{ number_format($item->free_quantity) }}</td>
                    <td class="num">{{ number_format($item->total_quantity) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">No items in this distribution.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">Totals ({{ $distribution->items->count() }} parties)</td>
                <td class="num">{{ number_format($distribution->total_paid_quantity) }}</td>
                <td class="num">{{ number_format($distribution->total_free_quantity) }}</td>
                <td class="num">{{ number_format($distribution->total_quantity) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="pdf-footer">
        Generated on {{ now()->format('d F Y, h:i A') }} &middot; {{ $distribution->company?->company_name ?? config('app.name') }}
    </div>

</body>
</html>
