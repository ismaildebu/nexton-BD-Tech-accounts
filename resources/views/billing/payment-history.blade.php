@extends('layouts.app')

@section('title', 'পেমেন্ট হিস্ট্রি')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">পেমেন্ট হিস্ট্রি</h1>
            <p class="text-gray-600 mt-2">সমস্ত সাবস্ক্রিপশন পেমেন্ট এবং লেনদেন</p>
        </div>

        @if ($payments->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">তারিখ</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">পরিমাণ</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">স্ট্যাটাস</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">পদ্ধতি</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">রেফারেন্স</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $payment->created_at->format('d M Y H:i') }}
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
                                    @elseif ($payment->status === 'refunded')
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">রিফান্ড</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $payment->payment_method ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $payment->transaction_reference ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $payments->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <p class="text-gray-600 mb-4">কোন পেমেন্ট রেকর্ড পাওয়া যায়নি</p>
                <a href="{{ route('billing.subscription') }}" class="text-blue-600 hover:text-blue-800">
                    আপনার সাবস্ক্রিপশনে ফিরে যান
                </a>
            </div>
        @endif
    </div>
</div>
@endsection