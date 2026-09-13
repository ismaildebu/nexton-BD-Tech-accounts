@extends('layouts.app')

@section('title', 'আমার সাবস্ক্রিপশন')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">বিলিং এবং সাবস্ক্রিপশন</h1>
            <p class="text-gray-600 mt-2">আপনার পরিকল্পনা এবং বিলিং তথ্য পরিচালনা করুন</p>
        </div>

        @if ($message = Session::get('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ $message }}
            </div>
        @endif

        @if ($message = Session::get('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                {{ $message }}
            </div>
        @endif

        <!-- Current Plan Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h2>
                    <p class="text-gray-600 mt-1">{{ $plan->description }}</p>
                </div>
                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                    সক্রিয়
                </span>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <p class="text-gray-500 text-sm">মূল্য</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $plan->price }} ৳</p>
                    <p class="text-gray-600 text-xs mt-1">{{ $plan->billing_cycle }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">শুরু</p>
                    <p class="text-xl font-semibold text-gray-900">
                        {{ $subscription->starts_at->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">স্ট্যাটাস</p>
                    <p class="text-xl font-semibold text-green-600">সক্রিয়</p>
                </div>
            </div>

            <div class="border-t pt-6 flex gap-4">
                <a href="{{ route('billing.plans') }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    প্ল্যান আপগ্রেড করুন
                </a>
                <form action="{{ route('billing.subscription.cancel') }}" method="POST" class="inline" onsubmit="return confirm('আপনি কি সাবস্ক্রিপশন বাতিল করতে চান?')">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        সাবস্ক্রিপশন বাতিল করুন
                    </button>
                </form>
            </div>
        </div>

        <!-- Plan Features -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-4">অন্তর্ভুক্ত বৈশিষ্ট্য</h3>
            <div class="grid grid-cols-2 gap-4">
                @foreach ($plan->features as $feature)
                    @if ($feature->is_enabled)
                        <div class="flex items-center">
                            <span class="text-green-600 mr-3">✓</span>
                            <span class="text-gray-700">
                                {{ ucfirst(str_replace('_', ' ', $feature->feature_key)) }}
                                @if ($feature->limit_value === -1)
                                    <span class="text-gray-500 text-sm">(আনলিমিটেড)</span>
                                @elseif ($feature->limit_value)
                                    <span class="text-gray-500 text-sm">(সীমা: {{ $feature->limit_value }})</span>
                                @endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('billing.payments') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <h3 class="font-bold text-gray-900 mb-2">পেমেন্ট হিস্ট্রি</h3>
                <p class="text-gray-600 text-sm">সমস্ত লেনদেন দেখুন</p>
            </a>
            <a href="{{ route('billing.plans') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <h3 class="font-bold text-gray-900 mb-2">সব পরিকল্পনা</h3>
                <p class="text-gray-600 text-sm">আপগ্রেড বা ডাউনগ্রেড করুন</p>
            </a>
        </div>
    </div>
</div>
@endsection