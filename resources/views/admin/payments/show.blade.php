@extends('layouts.app')

@section('title', 'পেমেন্ট বিস্তারিত')

@section('page-title', 'পেমেন্ট #' . $payment->id)

@section('page-subtitle', 'সম্পূর্ণ পেমেন্ট তথ্য এবং সাবস্ক্রিপশন বিবরণ')

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('admin.payments.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            <i class="bi bi-arrow-left mr-1"></i>পেমেন্ট ড্যাশবোর্ডে ফিরুন
        </a>
    </div>

    {{-- Payment Details Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            
            {{-- Left Column --}}
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-4">পেমেন্ট তথ্য</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-slate-600">পেমেন্ট ID</p>
                        <p class="text-lg font-semibold text-slate-900">#{{ $payment->id }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">পরিমাণ</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">অবস্থা</p>
                        <div class="mt-1">
                            @if ($payment->status === 'paid')
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">প্রদত্ত</span>
                            @elseif ($payment->status === 'pending')
                                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-semibold rounded-full">অপেক্ষমান</span>
                            @elseif ($payment->status === 'failed')
                                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-sm font-semibold rounded-full">ব্যর্থ</span>
                            @else
                                <span class="inline-block px-3 py-1 bg-slate-100 text-slate-800 text-sm font-semibold rounded-full">{{ $payment->status }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">পেমেন্ট পদ্ধতি</p>
                        <p class="text-sm font-semibold text-slate-900">{{ ucfirst($payment->payment_method) }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">লেনদেন রেফারেন্স</p>
                        <p class="text-sm font-mono text-slate-900">{{ $payment->transaction_reference ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            
            {{-- Right Column --}}
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-4">সাবস্ক্রিপশন বিবরণ</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-slate-600">সাবস্ক্রিপশন ID</p>
                        <p class="text-lg font-semibold text-slate-900">#{{ $payment->subscription->id }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">ব্যবহারকারী</p>
                        <div class="mt-1">
                            <p class="font-semibold text-slate-900">{{ $payment->subscription->user->name }}</p>
                            <p class="text-sm text-slate-600">{{ $payment->subscription->user->email }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">পরিকল্পনা</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $payment->subscription->plan->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-slate-600">সাবস্ক্রিপশন অবস্থা</p>
                        <p class="text-sm font-semibold text-slate-900">{{ ucfirst($payment->subscription->status) }}</p>
                    </div>
                </div>
            </div>
            
        </div>

        <hr class="my-6">

        {{-- Timestamps --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-slate-600">তৈরি</p>
                <p class="text-sm font-semibold text-slate-900">{{ $payment->created_at->format('d M Y H:i') }}</p>
            </div>
            
            @if ($payment->paid_at)
            <div>
                <p class="text-sm text-slate-600">প্রদত্ত</p>
                <p class="text-sm font-semibold text-slate-900">{{ $payment->paid_at->format('d M Y H:i') }}</p>
            </div>
            @endif
            
            <div>
                <p class="text-sm text-slate-600">আপডেট</p>
                <p class="text-sm font-semibold text-slate-900">{{ $payment->updated_at->format('d M Y H:i') }}</p>
            </div>
        </div>

    </div>

    {{-- Metadata (if any) --}}
    @if ($payment->metadata && !empty((array)$payment->metadata))
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">অতিরিক্ত তথ্য</h3>
        <div class="bg-slate-50 rounded-lg p-4 font-mono text-xs overflow-x-auto">
            <pre>{{ json_encode($payment->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif


            {{-- Admin Actions --}}
    @if ($payment->status === 'pending' && auth()->user()->role === 'Super Admin')
    <div class="bg-white rounded-xl shadow-sm border border-yellow-200 p-6 mt-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">অ্যাডমিন অ্যাকশন</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Verify Payment --}}
            <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" onsubmit="return confirm('এই পেমেন্ট যাচাই করবেন?');">
                @csrf
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    <i class="bi bi-check-circle mr-2"></i>
                    যাচাই করুন ও সক্রিয় করুন
                </button>
                <p class="text-xs text-slate-600 mt-2">এটি পেমেন্ট PAID করবে এবং সাবস্ক্রিপশন সক্রিয় করবে</p>
            </form>

            {{-- Reject Payment --}}
            <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" onsubmit="return confirm('এই পেমেন্ট প্রত্যাখ্যান করবেন?');">
                @csrf
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    <i class="bi bi-x-circle mr-2"></i>
                    প্রত্যাখ্যান করুন
                </button>
                <p class="text-xs text-slate-600 mt-2">এটি পেমেন্ট FAILED করবে এবং সাবস্ক্রিপশন বাতিল করবে</p>
            </form>
        </div>
    </div>
    @endif
</div>

@endsection