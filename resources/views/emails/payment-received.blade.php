<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <!-- Header -->
    <div style="background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">✓ Payment Received</h1>
    </div>

    <!-- Content -->
    <div style="background-color: white; padding: 30px; border-radius: 0 0 5px 5px;">
        <p>Dear {{ $customer->name }},</p>

        <p>Thank you for your payment. We have successfully received your payment.</p>

        <!-- Payment Details -->
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">Payment Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">৳ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Payment ID:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $payment->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Reference:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $payment->reference_id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Payment Method:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px;"><strong>Status:</strong></td>
                    <td style="padding: 8px;"><span style="background-color: #FFC107; color: white; padding: 5px 10px; border-radius: 3px;">{{ ucfirst($payment->status) }}</span></td>
                </tr>
            </table>
        </div>

        <!-- Invoice Details -->
        @if($invoice)
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">Invoice Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Invoice:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Total Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">৳ {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Paid Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">৳ {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px;"><strong>Due Amount:</strong></td>
                    <td style="padding: 8px;">৳ {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Message -->
        <div style="margin: 20px 0; padding: 15px; background-color: #E3F2FD; border-left: 4px solid #2196F3; color: #1565C0;">
            <p style="margin: 0;">
                <strong>Note:</strong> Your payment is under verification. Our team will confirm within 24 hours. You will receive a confirmation email once verified.
            </p>
        </div>

        <!-- Footer -->
        <div style="text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #666; font-size: 12px;">
            <p style="margin: 5px 0;">Thank you for your business!</p>
            <p style="margin: 5px 0;">© 2026 Nexton BD Tech. All rights reserved.</p>
        </div>
    </div>
</div>