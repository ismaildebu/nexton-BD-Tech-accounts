@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Invoice Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">Invoice Details</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Invoice Number:</span>
                    <span class="font-semibold">{{ $invoice->invoice_number }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Invoice Date:</span>
                    <span>{{ $invoice->invoice_date->format('M d, Y') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Due Date:</span>
                    <span>{{ $invoice->due_date->format('M d, Y') }}</span>
                </div>
                
                <div class="border-t pt-4 mt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-semibold">৳ {{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Paid Amount:</span>
                        <span class="font-semibold">৳ {{ number_format($invoice->paid_amount, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="text-gray-800 font-bold">Due Amount:</span>
                        <span class="text-xl font-bold text-red-600">৳ {{ number_format($dueAmount, 2) }}</span>
                    </div>
                </div>
                
                @if($invoice->status === 'paid')
                    <div class="mt-6 p-4 bg-green-100 text-green-700 rounded">
                        ✓ Invoice is fully paid
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">Make Payment</h2>
            
            <form id="paymentForm" action="{{ route('customer-payment.initiate', $referenceId) }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Amount -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Amount (৳)</label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="amount"
                        step="0.01" 
                        min="0.01"
                        max="{{ $dueAmount }}"
                        value="{{ $dueAmount }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    />
                    <small class="text-gray-500">Maximum: ৳ {{ number_format($dueAmount, 2) }}</small>
                    @error('amount')
                        <p class="text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Payment Method</label>
                    <select name="payment_method" id="paymentMethod" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="sslcommerz">SSLCommerz (Card/Mobile)</option>
                        <option value="bkash">Bkash Manual</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    @error('payment_method')
                        <p class="text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition"
                    id="submitBtn"
                >
                    Pay Now
                </button>

                @if($dueAmount <= 0)
                    <div class="p-4 bg-green-100 text-green-700 rounded">
                        ✓ This invoice has been paid
                    </div>
                @endif
            </form>

            <!-- Payment Methods Info -->
            <div class="mt-8 pt-6 border-t space-y-3">
                <h3 class="font-semibold text-gray-700">Payment Methods</h3>
                <div class="text-sm text-gray-600 space-y-2">
                    <p><strong>SSLCommerz:</strong> Pay with debit/credit card or mobile money</p>
                    <p><strong>Bkash:</strong> Manual payment - provide transaction details</p>
                    <p><strong>Bank Transfer:</strong> Direct bank deposit</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = parseFloat(document.getElementById('amount').value);
        const maxAmount = parseFloat(document.getElementById('amount').max);
        
        if (amount > maxAmount) {
            alert('Amount cannot exceed due amount');
            return;
        }
        
        // Loading state
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        this.submit();
    });
</script>
@endsection