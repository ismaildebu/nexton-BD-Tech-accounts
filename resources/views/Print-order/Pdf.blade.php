<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $order->order_number }}</title>
    <style>
        /* dompdf's rendering engine doesn't support flexbox/grid,
           so this template is written entirely table/block-based,
           matching resources/views/vouchers/pdf.blade.php. */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
        }
        .header-table { width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 18px; font-weight: bold; color: #111827; }
        .company-meta { font-size: 9px; color: #6b7280; }
        .order-box {
            border: 2px solid #1f2937;
            padding: 8px 14px;
            text-align: center;
        }
        .order-type-label { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; }
        .order-number { font-size: 16px; font-weight: bold; color: #111827; }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 6px;
        }
        .status-received { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-other { background: #e0e7ff; color: #3730a3; }

        .meta-table { width: 100%; margin-bottom: 16px; }
        .meta-table td { padding: 3px 0; font-size: 10px; vertical-align: top; }
        .meta-label { color: #6b7280; width: 130px; }
        .meta-value { color: #111827; font-weight: bold; }

        .qty-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .qty-box th {
            background: #1f2937;
            color: #ffffff;
            padding: 8px;
            font-size: 10px;
            text-align: center;
            border: 1px solid #1f2937;
        }
        .qty-box td {
            padding: 10px 8px;
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #d1d5db;
        }

        .notes-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 8px;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .notes-title { font-weight: bold; color: #374151; margin-bottom: 4px; }

        .signature-table { width: 100%; margin-top: 60px; }
        .signature-table td {
            width: 33%;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #9ca3af;
            padding-top: 6px;
        }
        .signature-name { color: #4b5563; font-weight: bold; }

        .pdf-footer {
            margin-top: 30px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $order->company?->company_name ?? config('app.name') }}</div>
                @if($order->company?->address)
                    <div class="company-meta">{{ $order->company->address }}</div>
                @endif
                @if($order->company?->phone)
                    <div class="company-meta">Tel: {{ $order->company->phone }}</div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="order-box">
                    <div class="order-type-label">Print Order</div>
                    <div class="order-number">{{ $order->order_number }}</div>
                    <div>
                        @php
                            $statusClass = match($order->status) {
                                'Received'  => 'status-received',
                                'Cancelled' => 'status-cancelled',
                                'Draft'     => 'status-draft',
                                default     => 'status-other',
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $order->status }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Meta info --}}
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <table class="meta-table">
                    <tr><td class="meta-label">Publication</td><td class="meta-value">{{ $order->publication?->name }}</td></tr>
                    <tr><td class="meta-label">Order Number</td><td>{{ $order->order_number }}</td></tr>
                    <tr><td class="meta-label">Order Date</td><td>{{ $order->order_date?->format('d F Y') }}</td></tr>
                    <tr><td class="meta-label">Print Date</td><td>{{ $order->print_date?->format('d F Y') ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="meta-table">
                    <tr><td class="meta-label">Printing Press</td><td>{{ $order->vendor?->name ?? '—' }}</td></tr>
                    @if($order->vendor?->phone)
                        <tr><td class="meta-label">Press Contact</td><td>{{ $order->vendor->phone }}</td></tr>
                    @endif
                    @if($order->printPlan)
                        <tr><td class="meta-label">From Print Plan</td><td>{{ $order->printPlan->plan_date?->format('d F Y') }}</td></tr>
                    @endif
                    <tr><td class="meta-label">Prepared By</td><td>{{ $order->creator?->name ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Quantities --}}
    <table class="qty-box">
        <thead>
            <tr>
                <th style="width: 33%;">Ordered Quantity</th>
                <th style="width: 33%;">Printed Quantity</th>
                <th style="width: 34%;">Received Quantity</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($order->ordered_quantity) }}</td>
                <td>{{ number_format($order->printed_quantity) }}</td>
                <td>{{ number_format($order->received_quantity) }}</td>
            </tr>
        </tbody>
    </table>

    @if($order->notes)
        <div class="notes-box">
            <div class="notes-title">Special Instructions</div>
            {{ $order->notes }}
        </div>
    @endif

    {{-- Authorized information / signatures --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-name">{{ $order->creator?->name ?? '—' }}</div>
                <div>Prepared By</div>
            </td>
            <td>
                <div class="signature-name">&nbsp;</div>
                <div>Approved By</div>
            </td>
            <td>
                <div class="signature-name">{{ $order->vendor?->name ?? '—' }}</div>
                <div>Printing Press / Vendor</div>
            </td>
        </tr>
    </table>

    <div class="pdf-footer">
        Generated on {{ now()->format('d F Y, h:i A') }} &middot; {{ $order->company?->company_name ?? config('app.name') }}
    </div>

</body>
</html>