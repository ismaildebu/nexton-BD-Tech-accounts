@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">Bkash Payment</h2>
        
        <!-- Payment Info -->
        <div class="bg-blue-50 p-4 rounded-lg mb-6">
            <div class="text-center">
                <p class="text-gray-600 mb-2">Amount to Pay</p>
                <p class="text-3xl font-bold text-blue-600">৳ {{ number_format($payment->amount, 2) }}</p>
            </div>
            
            <div class="mt-4 pt-4 border-t space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Reference ID:</span>
                    <span class="font-semibold">{{ $payment->reference_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Customer:</span>
                    <span class="font-semibold">{{ $payment->customer->customer_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Status:</span>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-yellow-800 mb-3">📱 Bkash Payment Instructions:</h3>
            <ol class="space-y-2 text-sm text-yellow-700 list-decimal list-inside">
                <li>Send ৳ {{ number_format($payment->amount, 2) }} to Bkash</li>
                <li>Go to <strong>Send Money</strong> option</li>
                <li>Enter receiver number: <span class="font-bold">{{ config('bkash.number') }}</span></li>
                <li>Complete the transaction</li>
                <li>Copy the transaction ID</li>
                <li>Enter the details below</li>
            </ol>
        </div>

        <!-- Bkash Form -->
        <form action="{{ route('customer-payment.bkash.submit', $payment->id) }}" method="POST" class="space-y-4">
            @csrf
            
            <!-- Transaction ID -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Bkash Transaction ID *</label>
                <input 
                    type="text" 
                    name="transaction_id" 
                    placeholder="e.g., R1234567890"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                />
                <small class="text-gray-500">Found in your Bkash transaction confirmation</small>
                @error('transaction_id')
                    <p class="text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sender Number -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Your Bkash Number *</label>
                <input 
                    type="text" 
                    name="sender_number" 
                    placeholder="e.g., 01XXXXXXXXX"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                />
                <small class="text-gray-500">The number you sent money from</small>
                @error('sender_number')
                    <p class="text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition"
            >
                Submit Payment Details
            </button>

            <!-- Back Link -->
            <a href="{{ route('customer-payment.show', $payment->reference_id) }}" class="block text-center text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Payment
            </a>
        </form>

        <!-- Security Note -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg text-xs text-gray-600">
            <strong>🔒 Security:</strong> Your payment details are encrypted and secure. The admin will verify your transaction within 24 hours.
        </div>
    </div>
</div>
@endsection