{{--
    Stock Transfers - Show View
    একটি নির্দিষ্ট স্টক ট্রান্সফারের বিস্তারিত তথ্য প্রদর্শন করে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Transfer Details</h1>
        <a href="{{ route('stock-transfers.index') }}" class="text-sm text-blue-600 hover:underline">
            &larr; Back to list
        </a>
    </div>

    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr class="{{ $stockTransfer->quantity >= 100 ? 'transfer-row-large' : '' }}">
                    <th class="px-4 py-3 text-left w-1/3 bg-gray-50 font-medium text-gray-600">Product</th>
                    <td class="px-4 py-3 text-gray-800">{{ $stockTransfer->product->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">From Warehouse</th>
                    <td class="px-4 py-3 text-gray-800">{{ $stockTransfer->fromWarehouse->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">To Warehouse</th>
                    <td class="px-4 py-3 text-gray-800">{{ $stockTransfer->toWarehouse->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Quantity</th>
                    <td class="px-4 py-3 text-gray-800">{{ $stockTransfer->quantity }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Transfer Date</th>
                    <td class="px-4 py-3 text-gray-800">{{ $stockTransfer->transfer_date->format('d M, Y') }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Recorded At</th>
                    <td class="px-4 py-3 text-gray-500">{{ $stockTransfer->created_at->format('d M, Y h:i A') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <form action="{{ route('stock-transfers.destroy', $stockTransfer) }}" method="POST"
              onsubmit="return confirm('Delete this transfer?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow transition">
                Delete Transfer
            </button>
        </form>
    </div>
</div>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        একটি নির্দিষ্ট ট্রান্সফার রেকর্ডের সম্পূর্ণ বিস্তারিত প্রদর্শন।

    টেস্টিং ধাপ:
        1. index পেজ থেকে যেকোনো "View" লিংকে ক্লিক করুন।
        2. সকল ফিল্ড (product, warehouses, quantity, date) সঠিক
           দেখাচ্ছে কিনা যাচাই করুন।
        3. quantity >= 100 হলে বড় ট্রান্সফার হাইলাইট হচ্ছে কিনা দেখুন।
        4. "Delete Transfer" বাটনে ক্লিক করে confirm dialog ও পরবর্তীতে
           index পেজে redirect হচ্ছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}