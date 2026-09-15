<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $payment->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: white;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .section-title {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
        }
        
        .section-content {
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .amount {
            font-size: 16px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-verified {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        
        th {
            background: #f8f9fa;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 2px solid #ddd;
            margin-top: 20px;
            font-size: 11px;
            color: #888;
        }
        
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(52, 152, 219, 0.1);
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">RECEIPT</div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>PAYMENT RECEIPT</h1>
            <p>Reference: {{ $payment->reference_id }}</p>
        </div>

        <!-- Payment Details -->
        <div class="section">
            <div class="section-title">Payment Details</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Payment ID:</span>
                    <span class="info-value">#{{ $payment->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount:</span>
                    <span class="info-value amount">৳ {{ number_format($payment->amount, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">{{ $payment->payment_date->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @if($payment->status === 'verified')
                            <span class="status-badge status-verified">✓ Verified</span>
                        @elseif($payment->status === 'received')
                            <span class="status-badge status-pending">⏳ Received</span>
                        @else
                            <span class="status-badge status-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value">{{ $payment->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        @if($customer)
        <div class="section">
            <div class="section-title">Customer Details</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $customer->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $customer->email ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $customer->phone ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Invoice Details -->
        @if($invoice)
        <div class="section">
            <div class="section-title">Invoice Details</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Invoice Number</td>
                            <td style="text-align: right;">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td>Invoice Date</td>
                            <td style="text-align: right;">{{ $invoice->invoice_date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td>Total Amount</td>
                            <td style="text-align: right;">৳ {{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Previously Paid</td>
                            <td style="text-align: right;">৳ {{ number_format($invoice->paid_amount - $payment->amount, 2) }}</td>
                        </tr>
                        <tr style="background: #f8f9fa;">
                            <td><strong>This Payment</strong></td>
                            <td style="text-align: right;"><strong class="amount">৳ {{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Paid</td>
                            <td style="text-align: right;">৳ {{ number_format($invoice->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Remaining Balance</td>
                            <td style="text-align: right;">৳ {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Voucher Details -->
        @if($voucher)
        <div class="section">
            <div class="section-title">Accounting Voucher</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Voucher Number:</span>
                    <span class="info-value">{{ $voucher->voucher_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Voucher Date:</span>
                    <span class="info-value">{{ $voucher->voucher_date->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">{{ $voucher->status }}</span>
                </div>
                
                @if($voucher->details && $voucher->details->count() > 0)
                <div style="margin-top: 10px;">
                    <strong style="font-size: 12px;">Ledger Entries:</strong>
                    <table style="margin-top: 5px;">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th style="text-align: right;">Debit</th>
                                <th style="text-align: right;">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voucher->details as $detail)
                            <tr>
                                <td>{{ $detail->account->account_name ?? 'N/A' }}</td>
                                <td style="text-align: right;">
                                    {{ $detail->debit_amount > 0 ? '৳ ' . number_format($detail->debit_amount, 2) : '-' }}
                                </td>
                                <td style="text-align: right;">
                                    {{ $detail->credit_amount > 0 ? '৳ ' . number_format($detail->credit_amount, 2) : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>Generated on:</strong> {{ now()->format('M d, Y H:i:s') }}</p>
            <p>© 2026 Nexton BD Tech. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 10px;">This is an electronically generated receipt. No signature required.</p>
        </div>
    </div>
</body>
</html>