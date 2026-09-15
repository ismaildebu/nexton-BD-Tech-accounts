@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Customer Payments</h1>
        <p class="text-gray-600">Manage and verify customer online payments</p>
    </div>

    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Payments</div>
            <div class="text-3xl font-bold text-gray-800">{{ $payments->total() }}</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Pending Verification</div>
            <div class="text-3xl font-bold text-yellow-600">
                {{ $payments->where('status', 'pending')->count() + $payments->where('status', 'received')->count() }}
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Verified</div>
            <div class="text-3xl font-bold text-green-600">
                {{ $payments->where('status', 'verified')->count() }}
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Failed</div>
            <div class="text-3xl font-bold text-red-600">
                {{ $payments->where('status', 'failed')->count() }}
            </div>
        </div>
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

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <input 
                    type="text" 
                    placeholder="Search by customer name or reference..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    id="searchInput"
                />
            </div>
            
            <!-- Status Filter -->
            <div>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="received">Received</option>
                    <option value="verified">Verified</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            
            <!-- Reset -->
            <div class="flex gap-2">
                <button onclick="resetFilters()" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 rounded-lg transition">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Payment ID</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Customer</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Reference</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Amount</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Method</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Date</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-800">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800">#{{ $payment->id }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-semibold text-gray-800">{{ $payment->customer->customer_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $payment->customer->email ?? $payment->customer->phone }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $payment->reference_id }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">৳ {{ number_format($payment->amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 capitalize">
                                    {{ str_replace('_', ' ', $payment->payment_method) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($payment->status === 'pending')
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">Pending</span>
                                    @elseif($payment->status === 'received')
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">Received</span>
                                    @elseif($payment->status === 'verified')
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Verified</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Failed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('customer-payment.admin.show', $payment->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                        বিস্তারিত
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-600">
                                    No payments found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t">
                {{ $payments->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p class="text-lg">No customer payments yet</p>
            </div>
        @endif
    </div>
</div>

<script>
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
    }
</script>
@endsection