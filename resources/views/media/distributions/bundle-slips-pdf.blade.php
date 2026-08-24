<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bundle Slips - {{ $distribution->publication?->name }} - {{ $distribution->distribution_date?->format('Y-m-d') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }

        .slip {
            border: 2px solid #1f2937;
            padding: 14px;
            page-break-after: always;
        }
        /* Don't leave a trailing blank page after the last slip. */
        .slip:last-child { page-break-after: auto; }

        .slip-header { text-align: center; border-bottom: 1px solid #1f2937; padding-bottom: 8px; margin-bottom: 10px; }
        .publication-name { font-size: 18px; font-weight: bold; color: #111827; }
        .company-name { font-size: 10px; color: #6b7280; }

        table.slip-body { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.slip-body td { padding: 6px 4px; font-size: 12px; border-bottom: 1px dotted #d1d5db; vertical-align: top; }
        .field-label { color: #6b7280; width: 110px; font-size: 10px; text-transform: uppercase; }
        .field-value { font-weight: bold; color: #111827; }

        .type-badge {
            display: inline-block; padding: 2px 8px; border-radius: 8px;
            font-size: 9px; font-weight: bold; text-transform: uppercase;
        }
        .type-agent { background: #e0e7ff; color: #3730a3; }
        .type-hawker { background: #fef3c7; color: #92400e; }

        table.qty-box { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.qty-box th { background: #1f2937; color: #fff; padding: 6px; font-size: 9px; border: 1px solid #1f2937; }
        table.qty-box td { padding: 12px; font-size: 18px; font-weight: bold; text-align: center; border: 1px solid #d1d5db; }

        .slip-footer { margin-top: 14px; font-size: 8px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>

    @foreach($items as $item)
        <div class="slip">
            <div class="slip-header">
                <div class="publication-name">{{ $distribution->publication?->name }}</div>
                <div class="company-name">{{ $distribution->company?->company_name ?? config('app.name') }}</div>
            </div>

            <table class="slip-body">
                <tr>
                    <td class="field-label">Date</td>
                    <td class="field-value">{{ $distribution->distribution_date?->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="field-label">Party Name</td>
                    <td class="field-value">{{ $item->party?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="field-label">Type</td>
                    <td>
                        <span class="type-badge {{ $item->party?->isAgent() ? 'type-agent' : 'type-hawker' }}">
                            {{ $item->party?->type ?? '—' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="field-label">Address</td>
                    <td class="field-value">
                        {{ $item->party?->address ?? '—' }}
                        @if($item->party?->area)
                            , {{ $item->party->area }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="field-label">Phone</td>
                    <td class="field-value">{{ $item->party?->phone ?? '—' }}</td>
                </tr>
            </table>

            <table class="qty-box">
                <thead>
                    <tr>
                        <th>Paid Copies</th>
                        <th>Free Copies</th>
                        <th>Total Copies</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format($item->paid_quantity) }}</td>
                        <td>{{ number_format($item->free_quantity) }}</td>
                        <td>{{ number_format($item->total_quantity) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="slip-footer">
                {{ $distribution->publication?->name }} &middot; {{ $distribution->distribution_date?->format('d M Y') }}
            </div>
        </div>
    @endforeach

</body>
</html>
