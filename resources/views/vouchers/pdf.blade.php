<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $transaction->voucher_number }}</title>
    <style>
        /* dompdf-এর রেন্ডারিং ইঞ্জিন flexbox/grid সাপোর্ট করে না,
           তাই এই টেমপ্লেট পুরোপুরি table/block-based লেখা হয়েছে। */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
        }
        .header-table { width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 18px; font-weight: bold; color: #111827; }
        .company-meta { font-size: 9px; color: #6b7280; }
        .voucher-box {
            border: 2px solid #1f2937;
            padding: 8px 14px;
            text-align: center;
        }
        .voucher-type-label { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; }
        .voucher-number { font-size: 16px; font-weight: bold; color: #111827; }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 6px;
        }
        .status-posted { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-draft { background: #fef3c7; color: #92400e; }

        .meta-table { width: 100%; margin-bottom: 16px; }
        .meta-table td { padding: 3px 0; font-size: 10px; vertical-align: top; }
        .meta-label { color: #6b7280; width: 110px; }
        .meta-value { color: #111827; font-weight: bold; }

        .narration-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 8px;
            margin-bottom: 16px;
            font-size: 10px;
        }

        table.detail-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.detail-table th {
            background: #1f2937;
            color: #ffffff;
            padding: 6px 8px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #1f2937;
        }
        table.detail-table td {
            padding: 6px 8px;
            font-size: 10px;
            border: 1px solid #d1d5db;
        }
        .text-right { text-align: right; }
        .total-row td {
            background: #f3f4f6;
            font-weight: bold;
        }

        .signature-table { width: 100%; margin-top: 60px; }
        .signature-table td {
            width: 33%;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #9ca3af;
            padding-top: 6px;
        }
        .signature-name { color: #4b5563; font-weight: bold; }
        .signature-date { color: #9ca3af; font-size: 9px; }

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
                <div class="company-name">{{ $transaction->company?->company_name ?? config('app.name') }}</div>
                @if($transaction->company?->address)
                    <div class="company-meta">{{ $transaction->company->address }}</div>
                @endif
                @if($transaction->company?->phone)
                    <div class="company-meta">Tel: {{ $transaction->company->phone }}</div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="voucher-box">
                    <div class="voucher-type-label">{{ $transaction->voucherType?->name }}</div>
                    <div class="voucher-number">{{ $transaction->voucher_number }}</div>
                    <div>
                        @php
                            $statusClass = match($transaction->status) {
                                'posted'    => 'status-posted',
                                'cancelled' => 'status-cancelled',
                                default     => 'status-draft',
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($transaction->status) }}</span>
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
                    <tr><td class="meta-label">Voucher Number</td><td class="meta-value">{{ $transaction->voucher_number }}</td></tr>
                    <tr><td class="meta-label">Voucher Date</td><td>{{ $transaction->voucher_date?->format('d F Y') }}</td></tr>
                    <tr><td class="meta-label">Voucher Type</td><td>{{ $transaction->voucherType?->name }}</td></tr>
                    <tr><td class="meta-label">Financial Year</td><td>{{ $transaction->financialYear?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="meta-table">
                    @if($transaction->reference_number)
                        <tr><td class="meta-label">Reference No</td><td>{{ $transaction->reference_number }}</td></tr>
                    @endif
                    <tr><td class="meta-label">Company</td><td>{{ $transaction->company?->company_name }}</td></tr>
                    @if($transaction->isPosted())
                        <tr><td class="meta-label">Posted On</td><td>{{ $transaction->posted_at?->format('d F Y') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if($transaction->narration)
        <div class="narration-box">
            <strong>Narration:</strong> {{ $transaction->narration }}
        </div>
    @endif

    {{-- Detail lines --}}
    <table class="detail-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Account</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 17%;" class="text-right">Debit</th>
                <th style="width: 18%;" class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->details as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $detail->account?->account_name ?? '—' }}</strong></td>
                    <td>{{ $detail->description ?? '—' }}</td>
                    <td class="text-right">{{ $detail->debit_amount > 0 ? number_format($detail->debit_amount, 2) : '—' }}</td>
                    <td class="text-right">{{ $detail->credit_amount > 0 ? number_format($detail->credit_amount, 2) : '—' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($transaction->total_debit, 2) }}</td>
                <td class="text-right">{{ number_format($transaction->total_credit, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-name">Prepared By</div>
                <div>{{ $transaction->creator?->name ?? '—' }}</div>
                <div class="signature-date">{{ $transaction->created_at?->format('d M Y') }}</div>
            </td>
            <td>
                <div class="signature-name">Checked By</div>
                <div>&nbsp;</div>
            </td>
            <td>
                <div class="signature-name">Approved By</div>
                @if($transaction->isPosted())
                    <div>{{ $transaction->poster?->name ?? '—' }}</div>
                    <div class="signature-date">{{ $transaction->posted_at?->format('d M Y') }}</div>
                @else
                    <div>&nbsp;</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="pdf-footer">
        Generated on {{ now()->format('d M Y, h:i A') }} — {{ config('app.name') }}
    </div>

</body>
</html>