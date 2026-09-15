<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <!-- Header -->
    <div style="background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">✓✓ Payment Verified</h1>
    </div>

    <!-- Content -->
    <div style="background-color: white; padding: 30px; border-radius: 0 0 5px 5px;">
        <p>Dear {{ $customer->name }},</p>

        <p style="color: #4CAF50; font-weight: bold; font-size: 16px;">Your payment has been verified and confirmed!</p>

        <!-- Payment Details -->
        <div style="background-color: #E8F5E9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2E7D32;">Payment Confirmed</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;"><strong>Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;">৳ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;"><strong>Payment ID:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;">#{{ $payment->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;"><strong>Verified At:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #C8E6C9;">{{ $payment->verified_at->format('M d, Y H:i') }}</td>
                </tr>
            </table>
        </div>

        <!-- Invoice Status -->
        @if($invoice)
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">Invoice Status</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Invoice:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Status:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        @if($invoice->status === 'paid')
                            <span style="background-color: #4CAF50; color: white; padding: 5px 10px; border-radius: 3px;">✓ Paid</span>
                        @else
                            <span style="background-color: #FF9800; color: white; padding: 5px 10px; border-radius: 3px;">Partial Payment</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px;"><strong>Due Amount:</strong></td>
                    <td style="padding: 8px;">৳ {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div style="text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #666; font-size: 12px;">
            <p style="margin: 5px 0;">Thank you for your payment!</p>
            <p style="margin: 5px 0;">© 2026 Nexton BD Tech. All rights reserved.</p>
        </div>
    </div>
</div>