@extends('layouts.app')

@section('title', 'পেমেন্ট মনিটরিং')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">পেমেন্ট মনিটরিং ড্যাশবোর্ড</h1>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">মোট পেমেন্ট</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['total_payments'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">মোট পরিমাণ</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_amount'], 2) }} ৳</p>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-6">
                <p class="text-green-600 text-sm font-semibold">প্রদত্ত</p>
                <p class="text-3xl font-bold text-green-600">{{ $stats['paid_count'] }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg shadow p-6">
                <p class="text-yellow-600 text-sm font-semibold">পেন্ডিং</p>
                <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_count'] }}</p>
            </div>
            <div class="bg-red-50 rounded-lg shadow p-6">
                <p class="text-red-600 text-sm font-semibold">ব্যর্থ</p>
                <p class="text-3xl font-bold text-red-600">{{ $stats['failed_count'] }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" action="{{ route('admin.payments.index') }}" class="flex gap-4 flex-wrap">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="ব্যবহারকারীর নাম বা ইমেইল"
                    value="{{ request('search') }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg"
                >
                
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">সব স্ট্যাটাস</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>প্রদত্ত</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>পেন্ডিং</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ব্যর্থ</option>
                </select>

                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">
                    ফিল্টার করুন
                </button>
                <a href="{{ route('admin.payments.index') }}" class="px-6 py-2 bg-gray-300 text-gray-900 rounded-lg">
                    রিসেট করুন
                </a>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">ব্যবহারকারী</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">প্ল্যান</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">পরিমাণ</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">স্ট্যাটাস</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">পদ্ধতি</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">তারিখ</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $payment->subscription->user->name }}</p>
                                    <p class="text-gray-500 text-xs">{{ $payment->subscription->user->email }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $payment->subscription->plan->name }}
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                {{ $payment->amount }} {{ $payment->currency }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($payment->status === 'paid')
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">প্রদত্ত</span>
                                @elseif ($payment->status === 'pending')
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">পেন্ডিং</span>
                                @elseif ($payment->status === 'failed')
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">ব্যর্থ</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $payment->payment_method ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $payment->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800">
                                    বিস্তারিত
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                কোন পেমেন্ট রেকর্ড পাওয়া যায়নি
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection