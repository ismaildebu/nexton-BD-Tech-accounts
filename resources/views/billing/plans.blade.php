@extends('layouts.app')

@section('title', 'আপগ্রেড করুন')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">আমাদের পরিকল্পনা</h1>
            <p class="text-gray-600 mt-2">আপনার প্রয়োজন অনুযায়ী সঠিক পরিকল্পনা নির্বাচন করুন</p>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach ($plans as $plan)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    @if ($plan->id === $currentPlanId)
                        <div class="bg-green-600 text-white px-4 py-2 text-center font-semibold">
                            বর্তমান পরিকল্পনা
                        </div>
                    @endif
                    
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 text-sm mb-4">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <p class="text-4xl font-bold text-gray-900">{{ $plan->price }}</p>
                            <p class="text-gray-500">৳ / {{ $plan->billing_cycle }}</p>
                        </div>

                        <!-- Features List -->
                        <div class="mb-6 border-t border-b py-6">
                            <h4 class="font-semibold text-gray-900 mb-3">অন্তর্ভুক্ত:</h4>
                            <ul class="space-y-2">
                                @foreach ($plan->features->take(8) as $feature)
                                    @if ($feature->is_enabled)
                                        <li class="text-gray-700 text-sm flex items-center">
                                            <span class="text-green-600 mr-2">✓</span>
                                            {{ ucfirst(str_replace('_', ' ', $feature->feature_key)) }}
                                            @if ($feature->limit_value === -1)
                                                <span class="text-gray-500 text-xs ml-auto">(আনলিমিটেড)</span>
                                            @elseif ($feature->limit_value)
                                                <span class="text-gray-500 text-xs ml-auto">({{ $feature->limit_value }})</span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>

                        @if ($plan->id === $currentPlanId)
                            <button class="w-full px-6 py-2 bg-gray-400 text-white rounded-lg cursor-default" disabled>
                                এটি আপনার বর্তমান পরিকল্পনা
                            </button>
                        @else
                            <form action="{{ route('billing.plans.initiate-payment', $plan) }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                
                                @if ((float) $plan->price > 0)
                                    <button type="submit" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                                        ৳{{ number_format((float) $plan->price, 2) }} দিয়ে আপগ্রেড করুন
                                    </button>
                                    <p class="text-xs text-gray-500 text-center mt-2">SSLCommerz দ্বারা নিরাপদ পেমেন্ট</p>
                                @else
                                    <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                        এই প্ল্যানে যান (বিনামূল্যে)
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Plan Comparison Table -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">বিস্তারিত তুলনা</h2>
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">বৈশিষ্ট্য</th>
                            @foreach ($plans as $plan)
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allFeatures = $plans->flatMap(fn($p) => $p->features)->pluck('feature_key')->unique();
                        @endphp
                        @foreach ($allFeatures as $featureKey)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $featureKey)) }}
                                </td>
                                @foreach ($plans as $plan)
                                    @php
                                        $feature = $plan->features->firstWhere('feature_key', $featureKey);
                                    @endphp
                                    <td class="px-6 py-4 text-center text-sm">
                                        @if ($feature && $feature->is_enabled)
                                            @if ($feature->limit_value === -1)
                                                <span class="text-green-600 font-semibold">আনলিমিটেড</span>
                                            @elseif ($feature->limit_value)
                                                <span class="text-green-600 font-semibold">{{ $feature->limit_value }}</span>
                                            @else
                                                <span class="text-green-600">✓</span>
                                            @endif
                                        @else
                                            <span class="text-gray-300">✗</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection