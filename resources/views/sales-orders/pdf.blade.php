<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">

    <title>{{ $salesOrder->so_number }}</title>

    <style>
        @page {
            margin: 15mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1e293b;
        }

        .header {
            border-bottom: 2px solid #1e293b;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
        }

        .number {
            font-size: 14px;
            margin-top: 4px;
        }

        .info-table,
        .items,
        .totals,
        .signatures {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .items {
            margin-top: 20px;
        }

        .items th,
        .items td {
            border: 1px solid #cbd5e1;
            padding: 8px;
        }

        .items th {
            background: #f1f5f9;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            margin-top: 15px;
        }

        .totals td {
            padding: 5px;
        }

        .grand-total {
            font-size: 15px;
            font-weight: bold;
            border-top: 2px solid #1e293b;
        }

        .amount-words {
            margin-top: 12px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }

        .notes {
            margin-top: 20px;
        }

        .signatures {
            margin-top: 75px;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding: 0 30px;
        }

        .signature-line {
            border-top: 1px solid #334155;
            padding-top: 8px;
        }
    </style>
</head>

<body>

<div class="header">
    <div class="title">Sales Order</div>
    <div class="number">{{ $salesOrder->so_number }}</div>
</div>

<table class="info-table">
    <tr>
        <td>
            <strong>Customer:</strong>
            {{ $salesOrder->customer->name ?? 'N/A' }}
        </td>

        <td>
            <strong>Order Date:</strong>
            {{ optional($salesOrder->order_date)->format('d M Y') }}
        </td>
    </tr>

    <tr>
        <td>
            <strong>Phone:</strong>
            {{ $salesOrder->customer->phone ?? 'N/A' }}
        </td>

        <td>
            <strong>Delivery Date:</strong>
            {{ optional($salesOrder->delivery_date)->format('d M Y') }}
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Description</th>
            <th>Qty</th>
            <th>Unit</th>
            <th class="text-right">Price</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>

    <tbody>
        @foreach($salesOrder->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td>
                    {{ $item->product->name ?? 'N/A' }}
                </td>

                <td>
                    {{ $item->description ?? '' }}
                </td>

                <td>
                    {{ number_format((float) $item->quantity, 2) }}
                </td>

                <td>
                    {{ $item->unit ?? '' }}
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

<table class="totals">
    <tr>
        <td class="text-right">Subtotal:</td>
        <td class="text-right">
            ৳{{ number_format((float) $salesOrder->subtotal, 2) }}
        </td>
    </tr>

    <tr>
        <td class="text-right">Tax:</td>
        <td class="text-right">
            ৳{{ number_format((float) $salesOrder->tax, 2) }}
        </td>
    </tr>

    <tr>
        <td class="text-right">Discount:</td>
        <td class="text-right">
            -৳{{ number_format((float) $salesOrder->discount, 2) }}
        </td>
    </tr>

    <tr class="grand-total">
        <td class="text-right">Total:</td>
        <td class="text-right">
            ৳{{ number_format((float) $salesOrder->total, 2) }}
        </td>
    </tr>
</table>

<div class="amount-words">
    Amount in Words: {{ $amountInWords }}
</div>

@if($salesOrder->notes)
    <div class="notes">
        <strong>Notes:</strong><br>
        {{ $salesOrder->notes }}
    </div>
@endif

<table class="signatures">
    <tr>
        <td>
            <div class="signature-line">
                Customer Signature
            </div>

            <div>
                {{ $salesOrder->customer->name ?? '' }}
            </div>
        </td>

        <td>
            <div class="signature-line">
                Authorized Signature
            </div>

            <div>
                For and on behalf of the company
            </div>
        </td>
    </tr>
</table>

</body>
</html>