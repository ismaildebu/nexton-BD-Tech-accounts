<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <!-- Header -->
    <div style="background-color: #FF9800; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">⚠️ New Payment Received</h1>
    </div>

    <!-- Content -->
    <div style="background-color: white; padding: 30px; border-radius: 0 0 5px 5px;">
        <p>Hello Admin,</p>

        <p>A new payment has been received and is awaiting your verification.</p>

        <!-- Payment Details -->
        <div style="background-color: #FFF3E0; padding: 15px; border-left: 4px solid #FF9800; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #E65100;">Payment Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;"><strong>Payment ID:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;">#{{ $payment->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;"><strong>Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;">৳ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;"><strong>Customer:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;">{{ $customer->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;"><strong>Reference:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;">{{ $payment->reference_id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;"><strong>Method:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #FFE0B2;">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px;"><strong>Status:</strong></td>
                    <td style="padding: 8px;"><span style="background-color: #FFC107; color: white; padding: 5px 10px; border-radius: 3px;">{{ ucfirst($payment->status) }}</span></td>
                </tr>
            </table>
        </div>

        <!-- Action Button -->
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ config('app.url') }}/admin/customer-payments/{{ $payment->id }}" 
               style="display: inline-block; background-color: #FF9800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Review Payment
            </a>
        </div>

        <!-- Footer -->
        <div style="text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #666; font-size: 12px;">
            <p style="margin: 5px 0;">© 2026 Nexton BD Tech. All rights reserved.</p>
        </div>
    </div>
</div>