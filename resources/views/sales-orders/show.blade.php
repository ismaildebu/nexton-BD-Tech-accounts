@extends('layouts.app')
@section('title', 'Sales Order')
@section('page-title', 'Sales Order Details')
@section('page-subtitle', $salesOrder->so_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ $salesOrder->so_number }}</h2>
                <p class="text-slate-500 text-sm mt-1">{{ $salesOrder->order_date->format('d M Y') }}</p>
            </div>
            @php
                $colors = [
                    'Draft'     => 'bg-gray-100 text-gray-700',
                    'Confirmed' => 'bg-blue-100 text-blue-700',
                    'Delivered' => 'bg-green-100 text-green-700',
                    'Cancelled' => 'bg-red-100 text-red-700',
                ];
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $colors[$salesOrder->status] ?? '' }}">
                {{ $salesOrder->status }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-slate-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Customer</p>
                <p class="font-semibold">{{ $salesOrder->customer->name }}</p>
                <p class="text-sm text-slate-600">{{ $salesOrder->customer->phone }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Delivery</p>
                <p class="text-sm">{{ $salesOrder->delivery_date?->format('d M Y') ?? 'N/A' }}</p>
            </div>
        </div>

        <table class="w-full text-sm mb-6">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Item</th>
                    <th class="text-left px-4 py-3 font-medium">Description</th>
                    <th class="text-right px-4 py-3 font-medium">Qty</th>
                    <th class="text-left px-4 py-3 font-medium">Unit</th>
                    <th class="text-right px-4 py-3 font-medium">Price</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($salesOrder->items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $item->item_name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $item->description ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                    <td class="px-4 py-3">{{ $item->unit ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">৳{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end mb-6">
            <div class="w-64 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">Subtotal:</span>
                    <span>৳{{ number_format($salesOrder->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Tax:</span>
                    <span>৳{{ number_format($salesOrder->tax, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Discount:</span>
                    <span>-৳{{ number_format($salesOrder->discount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base border-t pt-2">
                    <span>Total:</span>
                    <span>৳{{ number_format($salesOrder->total, 2) }}</span>
                </div>
            </div>
        </div>

        @if($salesOrder->notes)
        <div class="p-4 bg-slate-50 rounded-lg mb-4">
            <p class="text-xs font-medium text-slate-500 uppercase mb-1">Notes</p>
            <p class="text-sm">{{ $salesOrder->notes }}</p>
        </div>
        @endif

        {{-- Status Update --}}
        @if(!in_array($salesOrder->status, ['Cancelled', 'Delivered']))
        <div class="border-t pt-4">
            <p class="text-sm font-medium text-slate-700 mb-2">Update Status:</p>
            <div class="flex gap-2">
                @if($salesOrder->status === 'Draft')
                <form method="POST" action="{{ route('sales-orders.status', $salesOrder) }}">
                    @csrf
                    <input type="hidden" name="status" value="Confirmed">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        ✓ Confirm Order
                    </button>
                </form>
                @endif
                @if($salesOrder->status === 'Confirmed')
                <form method="POST" action="{{ route('sales-orders.status', $salesOrder) }}">
                    @csrf
                    <input type="hidden" name="status" value="Delivered">
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                        ✓ Mark as Delivered
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('sales-orders.status', $salesOrder) }}">
                    @csrf
                    <input type="hidden" name="status" value="Cancelled">
                    <button class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm hover:bg-red-200"
                            onclick="return confirm('Cancel this order?')">
                        Cancel Order
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('sales-orders.index') }}"
               class="text-sm text-blue-600 hover:underline">← Back to Sales Orders</a>
        </div>
    </div>
</div>
@endsection