<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<title>
    Purchase Order - {{ $purchaseOrder->po_number }}
</title>

<style>

    @page {
        size: A4;
        margin: 14mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #1e293b;
        line-height: 1.5;
    }

    .header {
        width: 100%;
        margin-bottom: 22px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-left {
        width: 70%;
        vertical-align: top;
    }

    .header-right {
        width: 30%;
        text-align: right;
        vertical-align: top;
    }

    .title {
        font-size: 22px;
        font-weight: bold;
        margin: 0 0 5px;
    }

    .po-number {
        font-size: 13px;
        color: #475569;
    }

    .status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
    }

    .status-draft {
        background: #f1f5f9;
        color: #475569;
    }

    .status-confirmed {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-received {
        background: #dcfce7;
        color: #15803d;
    }

    .status-cancelled {
        background: #fee2e2;
        color: #b91c1c;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 10px;
        vertical-align: top;
    }

    .info-label {
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 11px;
        font-weight: bold;
    }

    .muted {
        color: #64748b;
        font-size: 10px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
    }

    .items-table th {
        background: #f1f5f9;
        border-top: 1px solid #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
        padding: 8px 6px;
        font-size: 10px;
        text-align: left;
    }

    .items-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 7px 6px;
        vertical-align: top;
    }

    .text-right {
        text-align: right !important;
    }

    .text-center {
        text-align: center !important;
    }

    .totals-wrapper {
        width: 100%;
        margin-bottom: 18px;
    }

    .totals-table {
        width: 280px;
        margin-left: auto;
        border-collapse: collapse;
    }

    .totals-table td {
        padding: 4px 0;
    }

    .total-label {
        text-align: left;
        color: #475569;
    }

    .total-value {
        text-align: right;
    }

    .grand-total td {
        border-top: 1px solid #334155;
        padding-top: 7px;
        font-size: 13px;
        font-weight: bold;
    }

    .amount-words {
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        margin-bottom: 20px;
        font-size: 10px;
    }

    .amount-words-label {
        font-weight: bold;
        color: #475569;
    }

    .notes {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 10px;
        margin-bottom: 35px;
    }

    .notes-title {
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 50px;
    }

    .signature-cell {
        width: 40%;
        text-align: center;
        vertical-align: bottom;
    }

    .signature-space {
        height: 35px;
    }

    .signature-line {
        border-top: 1px solid #64748b;
        padding-top: 5px;
        font-size: 10px;
        color: #475569;
    }

    .footer {
        margin-top: 25px;
        text-align: center;
        color: #94a3b8;
        font-size: 8px;
    }

</style>
```

</head>

<body>

```
{{-- Header --}}
<div class="header">

    <table class="header-table">

        <tr>

            <td class="header-left">

                <div class="title">
                    PURCHASE ORDER
                </div>

                <div class="po-number">
                    {{ $purchaseOrder->po_number }}
                </div>

                <div class="muted">
                    Order Date:
                    {{ $purchaseOrder->order_date->format('d M Y') }}
                </div>

                @if($purchaseOrder->expected_date)

                    <div class="muted">
                        Expected Delivery:
                        {{ $purchaseOrder->expected_date->format('d M Y') }}
                    </div>

                @endif

            </td>

            <td class="header-right">

                @php
                    $statusClass = match ($purchaseOrder->status) {
                        'Confirmed' => 'status-confirmed',
                        'Received' => 'status-received',
                        'Cancelled' => 'status-cancelled',
                        default => 'status-draft',
                    };
                @endphp

                <span class="status {{ $statusClass }}">
                    {{ $purchaseOrder->status }}
                </span>

            </td>

        </tr>

    </table>

</div>

{{-- Vendor Information --}}
<table class="info-table">

    <tr>

        <td class="info-box" style="width: 55%;">

            <div class="info-label">
                Vendor
            </div>

            <div class="info-value">
                {{ $purchaseOrder->vendor->name }}
            </div>

            @if($purchaseOrder->vendor->phone)

                <div class="muted">
                    {{ $purchaseOrder->vendor->phone }}
                </div>

            @endif

            @if($purchaseOrder->vendor->email)

                <div class="muted">
                    {{ $purchaseOrder->vendor->email }}
                </div>

            @endif

        </td>

        <td style="width: 5%;"></td>

        <td class="info-box" style="width: 40%;">

            <div class="info-label">
                Delivery
            </div>

            <div class="muted">
                Expected:
                {{ $purchaseOrder->expected_date?->format('d M Y') ?? 'N/A' }}
            </div>

        </td>

    </tr>

</table>

{{-- Items --}}
<table class="items-table">

    <thead>

        <tr>

            <th style="width: 25%;">
                Item
            </th>

            <th style="width: 25%;">
                Description
            </th>

            <th class="text-right" style="width: 10%;">
                Qty
            </th>

            <th style="width: 10%;">
                Unit
            </th>

            <th class="text-right" style="width: 15%;">
                Price
            </th>

            <th class="text-right" style="width: 15%;">
                Total
            </th>

        </tr>

    </thead>

    <tbody>

        @foreach($purchaseOrder->items as $item)

            <tr>

                <td>
                    {{ $item->item_name }}
                </td>

                <td>
                    {{ $item->description ?? '-' }}
                </td>

                <td class="text-right">
                    {{ $item->quantity }}
                </td>

                <td>
                    {{ $item->unit ?? '-' }}
                </td>

                <td class="text-right">
                    ৳{{ number_format((float) $item->unit_price, 2) }}
                </td>

                <td class="text-right">
                    ৳{{ number_format((float) $item->total, 2) }}
                </td>

            </tr>

        @endforeach

    </tbody>

</table>

{{-- Totals --}}
<div class="totals-wrapper">

    <table class="totals-table">

        <tr>

            <td class="total-label">
                Subtotal:
            </td>

            <td class="total-value">
                ৳{{ number_format((float) $purchaseOrder->subtotal, 2) }}
            </td>

        </tr>

        <tr>

            <td class="total-label">
                Tax
                ({{ number_format((float) $taxPercentage, 2) }}%):
            </td>

            <td class="total-value">
                ৳{{ number_format((float) $purchaseOrder->tax, 2) }}
            </td>

        </tr>

        <tr>

            <td class="total-label">
                Discount
                ({{ number_format((float) $discountPercentage, 2) }}%):
            </td>

            <td class="total-value">
                -৳{{ number_format((float) $purchaseOrder->discount, 2) }}
            </td>

        </tr>

        <tr class="grand-total">

            <td class="total-label">
                Total:
            </td>

            <td class="total-value">
                ৳{{ number_format((float) $purchaseOrder->total, 2) }}
            </td>

        </tr>

    </table>

</div>

{{-- Amount in Words --}}
<div class="amount-words">

    <span class="amount-words-label">
        Amount in Words:
    </span>

    {{ $amountInWords }}

</div>

{{-- Notes --}}
@if($purchaseOrder->notes)

    <div class="notes">

        <div class="notes-title">
            Notes
        </div>

        <div>
            {{ $purchaseOrder->notes }}
        </div>

    </div>

@endif

{{-- Signatures --}}
<table class="signature-table">

    <tr>

        <td class="signature-cell">

            <div class="signature-space"></div>

            <div class="signature-line">
                Authorized By
            </div>

        </td>

        <td style="width: 20%;"></td>

        <td class="signature-cell">

            <div class="signature-space"></div>

            <div class="signature-line">
                Vendor
            </div>

        </td>

    </tr>

</table>

<div class="footer">
    Purchase Order - {{ $purchaseOrder->po_number }}
</div>
```

</body>

</html>
