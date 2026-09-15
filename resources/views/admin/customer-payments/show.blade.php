@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Details</h1>
            <p class="text-gray-600">Payment ID: #{{ $payment->id }}</p>
        </div>
        <a href="{{ route('customer-payment.admin.list') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
            ← Back to Payments
        </a>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="md:col-span-2">
            <!-- Payment Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Payment Information</h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Payment Status</div>
                        <div>
                            @if($payment->status === 'pending')
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-semibold">Pending</span>
                            @elseif($payment->status === 'received')
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold">Received</span>
                            @elseif($payment->status === 'verified')
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">✓ Verified</span>
                            @elseif($payment->status === 'failed')
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">✗ Failed</span>
                            @endif
                        </div>
                    </div>

                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Amount</div>
                        <div class="text-2xl font-bold text-gray-800">৳ {{ number_format($payment->amount, 2) }}</div>
                    </div>

                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Payment Method</div>
                        <div class="capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</div>
                    </div>

                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Payment Date</div>
                        <div>{{ $payment->payment_date->format('M d, Y') }}</div>
                    </div>

                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Reference ID</div>
                        <div class="font-semibold">{{ $payment->reference_id }}</div>
                    </div>

                    <div class="pb-4 border-b">
                        <div class="text-gray-600 text-sm font-semibold mb-1">Transaction Reference</div>
                        <div class="font-mono text-sm break-all">{{ $payment->transaction_reference ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Customer Information</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Customer Name:</span>
                        <span class="font-semibold">{{ $payment->customer->customer_name }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-semibold">{{ $payment->customer->email ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-semibold">{{ $payment->customer->phone ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Address:</span>
                        <span class="font-semibold text-right">{{ $payment->customer->address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Invoice Info -->
            @if($payment->invoice)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Invoice Information</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Invoice Number:</span>
                        <span class="font-semibold">{{ $payment->invoice->invoice_number }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Invoice Date:</span>
                        <span class="font-semibold">{{ $payment->invoice->invoice_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-semibold">৳ {{ number_format($payment->invoice->total_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Paid Amount:</span>
                        <span class="font-semibold">৳ {{ number_format($payment->invoice->paid_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b">
                        <span class="text-gray-600">Due Amount:</span>
                        <span class="font-semibold">৳ {{ number_format($payment->invoice->total_amount - $payment->invoice->paid_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold capitalize">{{ $payment->invoice->status }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Voucher Info -->
            @if($payment->voucher)
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-green-800">✓ Voucher Created</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between pb-3 border-b border-green-200">
                        <span class="text-green-700">Voucher Number:</span>
                        <span class="font-semibold">{{ $payment->voucher->voucher_number }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b border-green-200">
                        <span class="text-green-700">Voucher Date:</span>
                        <span class="font-semibold">{{ $payment->voucher->voucher_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between pb-3 border-b border-green-200">
                        <span class="text-green-700">Status:</span>
                        <span class="font-semibold">{{ $payment->voucher->status }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-green-700">Ledger Entries:</span>
                        <span class="font-semibold">{{ $payment->voucher->transactionDetails->count() }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if($payment->metadata)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Additional Information</h2>
                <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto">{{ json_encode($payment->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>

        <!-- Sidebar Actions -->
        <div>
            <!-- Action Buttons -->
            @if($payment->status === 'pending' || $payment->status === 'received')
            <div class="bg-white rounded-lg shadow p-6 mb-6 sticky top-8">
                <h2 class="text-lg font-bold mb-4 text-gray-800">Actions</h2>
                
                <!-- Verify Button -->
                <form action="{{ route('customer-payment.verify', $payment->id) }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
                        ✓ যাচাই করুন (Verify)
                    </button>
                </form>

                <!-- Reject Modal Trigger -->
                <button 
                    type="button" 
                    onclick="showRejectModal()" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition"
                >
                    ✗ প্রত্যাখ্যান (Reject)
                </button>
            </div>
            @endif

            <!-- Verification Info -->
            @if($payment->verified_by)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h2 class="text-lg font-bold mb-3 text-blue-800">Verification Info</h2>
                <div class="space-y-2 text-sm text-blue-700">
                    <div class="flex justify-between">
                        <span>Verified By:</span>
                        <span class="font-semibold">{{ $payment->verifiedBy->name ?? 'Admin' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Verified At:</span>
                        <span class="font-semibold">{{ $payment->verified_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Reject Payment</h2>
        
        <form action="{{ route('customer-payment.reject', $payment->id) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Rejection Reason</label>
                <textarea 
                    name="reason" 
                    rows="4"
                    placeholder="Why are you rejecting this payment?"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button 
                    type="button" 
                    onclick="hideRejectModal()" 
                    class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 rounded-lg transition"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg transition"
                >
                    Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showRejectModal() {
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function hideRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endsection