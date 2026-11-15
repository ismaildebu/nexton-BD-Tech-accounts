@extends('layouts.app')

@section('title', 'ব্যবহারকারীর পেমেন্ট')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-gray-500">{{ $user->email }}</p>
            </div>

            <a href="{{ route('admin.payments.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-900 rounded-lg">
                সব পেমেন্ট
            </a>
        </div>

        <!-- Subscriptions -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <h2 class="px-6 py-4 text-lg font-semibold text-gray-900 border-b">সাবস্ক্রিপশন</h2>

            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">প্ল্যান</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">স্ট্যাটাস</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">তৈরির তারিখ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $subscription)
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $subscription->plan?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->status }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                কোন সাবস্ক্রিপশন নেই
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Payments -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="px-6 py-4 text-lg font-semibold text-gray-900 border-b">পেমেন্ট</h2>

            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
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
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->payment_method ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.payments.show', $payment) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    বিস্তারিত
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                কোন পেমেন্ট রেকর্ড পাওয়া যায়নি
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
