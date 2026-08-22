@extends('layouts.app')

@section('page-title', 'Edit Print Order — ' . $order->order_number)
@section('page-subtitle', 'Only Draft orders can be edited')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Order Details</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('media.print-orders.update', $order) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Printing Press (Vendor)</label>
                <select name="vendor_id"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Not selected —</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $order->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Order Date *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', $order->order_date->toDateString()) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Print Date</label>
                    <input type="date" name="print_date" value="{{ old('print_date', $order->print_date?->toDateString()) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ordered Quantity *</label>
                <input type="number" name="ordered_quantity" min="1" value="{{ old('ordered_quantity', $order->ordered_quantity) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @if($order->printPlan)
                    <p class="text-xs text-amber-600 mt-1">This order originated from an approved print plan ({{ number_format($order->printPlan->final_quantity) }} copies approved).</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Special Instructions / Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Update Print Order
                </button>
                <a href="{{ route('media.print-orders.show', $order) }}" class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection