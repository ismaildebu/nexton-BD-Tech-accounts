@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <!-- Success Icon -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-green-600 mb-2">Payment Successful!</h1>
            <p class="text-gray-600">Your payment has been received and processed.</p>
        </div>

        <!-- Payment Details -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-800">Payment Details</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between pb-3 border-b">
                    <span class="text-gray-600">Payment ID:</span>
                    <span class="font-semibold">{{ $payment->id }}</span>
                </div>
                
                <div class="flex justify-between pb-3 border-b">
                    <span class="text-gray-600">Amount:</span>
                    <span class="font-semibold">৳ {{ number_format($payment->amount, 2) }}</span>
                </div>
                
                <div class="flex justify-between pb-3 border-b">
                    <span class="text-gray-600">Reference ID:</span>
                    <span class="font-semibold">{{ $payment->reference_id }}</span>
                </div>
                
                <div class="flex justify-between pb-3 border-b">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="font-semibold capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                </div>
                
                <div class="flex justify-between pb-3 border-b">
                    <span class="text-gray-600">Status:</span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm font-semibold">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
                
                <div class="flex justify-between pt-3">
                    <span class="text-gray-600">Date & Time:</span>
                    <span class="font-semibold">{{ $payment->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Voucher Info (if created) -->
        @if($payment->voucher)
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 text-green-800">✓ Voucher Created</h2>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-green-700">Voucher Number:</span>
                    <span class="font-semibold">{{ $payment->voucher->voucher_number }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-green-700">Voucher Date:</span>
                    <span class="font-semibold">{{ $payment->voucher->voucher_date->format('M d, Y') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-green-700">Status:</span>
                    <span class="font-semibold">{{ $payment->voucher->status }}</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-3 text-blue-800">Next Steps</h2>
            <ul class="space-y-2 text-sm text-blue-700">
                @if($payment->status === 'pending' || $payment->status === 'received')
                    <li>✓ Payment received by system</li>
                    <li>⏳ Admin will verify your payment within 24 hours</li>
                    <li>📧 You will receive confirmation via email</li>
                @elseif($payment->status === 'verified')
                    <li>✓ Payment verified by admin</li>
                    <li>✓ Invoice updated</li>
                    <li>📄 Accounting voucher created</li>
                @endif
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <a href="{{ route('customer-payment.show', $payment->reference_id) }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                View Invoice
            </a>
            
            <a href="{{ route('dashboard') }}" class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 rounded-lg transition">
                Back to Dashboard
            </a>
        </div>

        <!-- Print Receipt -->
        <button onclick="window.print()" class="w-full mt-4 text-center text-blue-600 hover:text-blue-800 font-semibold py-2">
            🖨️ Print Receipt
        </button>
        <!-- Download PDF Receipt -->
        <a href="{{ route('customer-payment.receipt.download', $payment->id) }}" class="w-full mt-2 block text-center text-green-600 hover:text-green-800 font-semibold py-2">
            📥 Download Receipt (PDF)
        </a>
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none;
        }
    }
</style>
@endsection