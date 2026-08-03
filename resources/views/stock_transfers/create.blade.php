{{--
    Stock Transfers - Create View
    নতুন স্টক ট্রান্সফার তৈরির ফর্ম।
    Product ও Warehouse ড্রপডাউন এবং TailwindCSS ফর্ম স্টাইলিং ব্যবহৃত হয়েছে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">New Stock Transfer</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-transfers.store') }}" method="POST"
          class="bg-white p-6 rounded-lg shadow ring-1 ring-gray-200 space-y-5">
        @csrf

        {{-- প্রোডাক্ট নির্বাচন --}}
        <div>
            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Product</label>
            <select name="product_id" id="product_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Select Product --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- সোর্স ওয়্যারহাউজ --}}
        <div>
            <label for="from_warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">From Warehouse</label>
            <select name="from_warehouse_id" id="from_warehouse_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Select Warehouse --</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('from_warehouse_id') == $warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ডেস্টিনেশন ওয়্যারহাউজ --}}
        <div>
            <label for="to_warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">To Warehouse</label>
            <select name="to_warehouse_id" id="to_warehouse_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Select Warehouse --</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('to_warehouse_id') == $warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">Must be different from the source warehouse.</p>
        </div>

        {{-- পরিমাণ --}}
        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input type="number" name="quantity" id="quantity" min="1" required
                   value="{{ old('quantity') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        {{-- ট্রান্সফার তারিখ --}}
        <div>
            <label for="transfer_date" class="block text-sm font-medium text-gray-700 mb-1">Transfer Date</label>
            <input type="date" name="transfer_date" id="transfer_date" required
                   value="{{ old('transfer_date', date('Y-m-d')) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('stock-transfers.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
                Save Transfer
            </button>
        </div>
    </form>
</div>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        নতুন স্টক ট্রান্সফার তৈরির ফর্ম প্রদর্শন এবং client-side
        (HTML5 required/min) + server-side validation যাচাই।

    টেস্টিং ধাপ:
        1. /stock-transfers/create ভিজিট করুন।
        2. ফর্ম খালি রেখে সাবমিট করে required validation যাচাই করুন।
        3. quantity=0 বা নেগেটিভ দিয়ে সাবমিট করলে error আসছে কিনা দেখুন।
        4. from_warehouse ও to_warehouse একই নির্বাচন করে সাবমিট করলে
           error message দেখাচ্ছে কিনা যাচাই করুন।
        5. সঠিক তথ্য দিয়ে সাবমিট করলে index পেজে redirect ও success
           message দেখাচ্ছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}