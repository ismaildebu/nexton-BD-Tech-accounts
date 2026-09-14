@extends('layouts.app')

@section('title', 'পেমেন্ট পদ্ধতি নির্বাচন')

@section('page-title', '{{ $plan->name }} প্ল্যানে আপগ্রেড')

@section('page-subtitle', 'পেমেন্ট পদ্ধতি নির্বাচন করুন এবং প্রক্রিয়া সম্পন্ন করুন')

@section('content')

<div x-data="{ paymentMethod: 'bkash' }" class="max-w-2xl mx-auto">

    {{-- Plan Summary Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $plan->name }} প্ল্যান</h2>
                <p class="text-slate-600 mt-1">{{ $plan->description }}</p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-emerald-600">
                    {{ number_format($plan->price, 2) }}
                </div>
                <div class="text-sm text-slate-600">৳ / মাসিক</div>
            </div>
        </div>
    </div>

    {{-- Payment Method Selection --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        {{-- Bkash Option --}}
        <button type="button" 
                @click="paymentMethod = 'bkash'"
                class="relative p-6 border-2 rounded-xl transition-all duration-200"
                :class="paymentMethod === 'bkash' 
                    ? 'border-emerald-500 bg-emerald-50' 
                    : 'border-slate-200 bg-white hover:border-slate-300'">
            <div class="flex items-start gap-4">
                <div class="flex-1 text-left">
                    <h3 class="text-lg font-bold text-slate-900">বিকাশ</h3>
                    <p class="text-sm text-slate-600 mt-1">মোবাইল ব্যাংকিং এর মাধ্যমে পেমেন্ট করুন</p>
                    <div class="mt-3 text-xs font-semibold text-emerald-700 bg-emerald-100 rounded px-2 py-1 inline-block">
                        ডিজিটাল পেমেন্ট
                    </div>
                </div>
                <div :class="paymentMethod === 'bkash' 
                    ? 'text-emerald-600' 
                    : 'text-slate-400'">
                    <i class="bi bi-phone text-2xl"></i>
                </div>
            </div>
            <div v-if="paymentMethod === 'bkash'" class="mt-4 pt-4 border-t border-emerald-200">
                <div class="text-xs font-semibold text-emerald-700">নির্বাচিত</div>
            </div>
        </button>

        {{-- SSLCommerz Option --}}
        <button type="button" 
                @click="paymentMethod = 'sslcommerz'"
                class="relative p-6 border-2 rounded-xl transition-all duration-200"
                :class="paymentMethod === 'sslcommerz' 
                    ? 'border-blue-500 bg-blue-50' 
                    : 'border-slate-200 bg-white hover:border-slate-300'">
            <div class="flex items-start gap-4">
                <div class="flex-1 text-left">
                    <h3 class="text-lg font-bold text-slate-900">SSLCommerz</h3>
                    <p class="text-sm text-slate-600 mt-1">কার্ড ও অনলাইন পেমেন্ট</p>
                    <div class="mt-3 text-xs font-semibold text-blue-700 bg-blue-100 rounded px-2 py-1 inline-block">
                        সকল পেমেন্ট গেটওয়ে
                    </div>
                </div>
                <div :class="paymentMethod === 'sslcommerz' 
                    ? 'text-blue-600' 
                    : 'text-slate-400'">
                    <i class="bi bi-credit-card text-2xl"></i>
                </div>
            </div>
            <div v-if="paymentMethod === 'sslcommerz'" class="mt-4 pt-4 border-t border-blue-200">
                <div class="text-xs font-semibold text-blue-700">নির্বাচিত</div>
            </div>
        </button>

    </div>

    {{-- Bkash Payment Form --}}
    <div v-show="paymentMethod === 'bkash'" x-transition class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        
        <h3 class="text-lg font-bold text-slate-900 mb-4">বিকাশ পেমেন্ট তথ্য</h3>

        {{-- Bkash Account Info --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="bi bi-info-circle text-emerald-600 text-xl mt-0.5"></i>
                <div>
                    <p class="font-semibold text-emerald-900">নিচের নম্বরে পেমেন্ট করুন:</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-2">{{ $bkashNumber }}</p>
                    <p class="text-sm text-emerald-700 mt-2">বিকাশ এপে "Send Money" অপশন ব্যবহার করুন</p>
                </div>
            </div>
        </div>

        <form action="{{ route('billing.plans.submit-bkash', $plan) }}" method="POST" class="space-y-4">
            @csrf

            {{-- Transaction ID --}}
            <div>
                <label for="transaction_id" class="block text-sm font-semibold text-slate-900 mb-2">
                    ট্রানজ্যাকশন ID <span class="text-red-600">*</span>
                </label>
                <input type="text"
                       id="transaction_id"
                       name="transaction_id"
                       placeholder="যেমন: TXN123456789"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('transaction_id') border-red-500 @enderror"
                       value="{{ old('transaction_id') }}"
                       required>
                @error('transaction_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-600 mt-1">বিকাশ এপ থেকে Transaction ID কপি করে পেস্ট করুন</p>
            </div>

            {{-- Sender Number --}}
            <div>
                <label for="sender_number" class="block text-sm font-semibold text-slate-900 mb-2">
                    আপনার বিকাশ নম্বর <span class="text-red-600">*</span>
                </label>
                <input type="tel"
                       id="sender_number"
                       name="sender_number"
                       placeholder="যেমন: 01700000000"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('sender_number') border-red-500 @enderror"
                       value="{{ old('sender_number') }}"
                       pattern="01[0-9]{9}"
                       required>
                @error('sender_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-600 mt-1">যে নম্বর থেকে পেমেন্ট করেছেন সেটি লিখুন</p>
            </div>

            {{-- Amount Info --}}
            <div class="bg-slate-50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-700 font-medium">প্রেরণ করুন:</span>
                    <span class="text-2xl font-bold text-slate-900">
                        {{ number_format($plan->price, 2) }} ৳
                    </span>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition-colors duration-200">
                <i class="bi bi-check-circle mr-2"></i>
                পেমেন্ট নিশ্চিত করুন
            </button>

            {{-- Cancel Button --}}
            <a href="{{ route('billing.plans') }}"
               class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold py-3 rounded-lg transition-colors duration-200">
                বাতিল করুন
            </a>

        </form>

    </div>

    {{-- SSLCommerz Message --}}
    <div v-show="paymentMethod === 'sslcommerz'" x-transition class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        
        <div class="text-center py-12">
            <i class="bi bi-hourglass-split text-blue-500 text-4xl mb-4"></i>
            <h3 class="text-lg font-bold text-slate-900 mb-2">SSLCommerz পেমেন্ট</h3>
            <p class="text-slate-600 mb-6">এই পেমেন্ট গেটওয়ে শীঘ্রই সক্রিয় হবে।</p>
            
            <form action="{{ route('billing.plans.initiate-payment', $plan) }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors duration-200">
                    <i class="bi bi-credit-card mr-2"></i>
                    SSLCommerz এ যান
                </button>
            </form>
        </div>

    </div>

</div>

@endsection