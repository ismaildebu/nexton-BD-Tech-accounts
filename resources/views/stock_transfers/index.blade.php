{{--
    Stock Transfers - Index View
    সকল স্টক ট্রান্সফারের তালিকা প্রদর্শন করে।
    TailwindCSS দিয়ে রেসপন্সিভ টেবিল লেআউট ব্যবহার করা হয়েছে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800">Stock Transfers</h1>

        <a href="{{ route('stock-transfers.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
            + New Transfer
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded-md bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">From Warehouse</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">To Warehouse</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Quantity</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Transfer Date</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($transfers as $transfer)
                    <tr class="hover:bg-gray-50 {{ $transfer->quantity >= 100 ? 'transfer-row-large' : '' }}">
                        <td class="px-4 py-3 text-gray-800">{{ $transfer->product->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $transfer->fromWarehouse->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $transfer->toWarehouse->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $transfer->quantity }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $transfer->transfer_date->format('d M, Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('stock-transfers.show', $transfer) }}"
                               class="text-blue-600 hover:underline">View</a>

                            <form action="{{ route('stock-transfers.destroy', $transfer) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Delete this transfer?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">No stock transfers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transfers->links() }}
    </div>
</div>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        Stock Transfer তালিকা প্রদর্শন, পেজিনেশন এবং ডিলিট action।

    টেস্টিং ধাপ:
        1. ব্রাউজারে /stock-transfers ভিজিট করুন।
        2. টেবিলে সকল কলাম সঠিকভাবে দেখাচ্ছে কিনা যাচাই করুন।
        3. quantity >= 100 হলে রো বোল্ড/হাইলাইট হচ্ছে কিনা দেখুন
           (inventory.css এর transfer-row-large ক্লাস চেক করুন)।
        4. "New Transfer" বাটনে ক্লিক করে create পেজে যাচ্ছে কিনা যাচাই করুন।
        5. Delete বাটনে ক্লিক করে confirm dialog আসছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}