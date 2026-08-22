@extends('layouts.app')

@section('title', 'Purchase Order')
@section('page-title', 'Purchase Order Details')
@section('page-subtitle', $purchaseOrder->po_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">

        {{-- Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $purchaseOrder->po_number }}</h2>
                <p class="text-slate-500 text-sm mt-1">{{ $purchaseOrder->order_date->format('d M Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $colors = [
                        'Draft'     => 'bg-gray-100 text-gray-700',
                        'Confirmed' => 'bg-blue-100 text-blue-700',
                        'Received'  => 'bg-green-100 text-green-700',
                        'Cancelled' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $colors[$purchaseOrder->status] ?? '' }}">
                    {{ $purchaseOrder->status }}
                </span>
            </div>
        </div>

        {{-- Vendor Info --}}
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-slate-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Vendor</p>
                <p class="font-semibold">{{ $purchaseOrder->vendor->name }}</p>
                <p class="text-sm text-slate-600">{{ $purchaseOrder->vendor->phone }}</p>
                <p class="text-sm text-slate-600">{{ $purchaseOrder->vendor->email }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Delivery</p>
                <p class="text-sm">Expected: {{ $purchaseOrder->expected_date?->format('d M Y') ?? 'N/A' }}</p>
            </div>
        </div>

        {{-- Items --}}
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
                @foreach($purchaseOrder->items as $item)
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

        {{-- Totals --}}
        <div class="flex justify-end mb-6">
            <div class="w-64 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">Subtotal:</span>
                    <span>৳{{ number_format($purchaseOrder->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Tax:</span>
                    <span>৳{{ number_format($purchaseOrder->tax, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Discount:</span>
                    <span>-৳{{ number_format($purchaseOrder->discount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base border-t pt-2">
                    <span>Total:</span>
                    <span>৳{{ number_format($purchaseOrder->total, 2) }}</span>
                </div>
            </div>
        </div>

        @if($purchaseOrder->notes)
        <div class="p-4 bg-slate-50 rounded-lg mb-6">
            <p class="text-xs font-medium text-slate-500 uppercase mb-1">Notes</p>
            <p class="text-sm">{{ $purchaseOrder->notes }}</p>
        </div>
        @endif

        {{-- Status Update --}}
        @if($purchaseOrder->status !== 'Cancelled' && $purchaseOrder->status !== 'Received')
        <div class="border-t pt-4">
            <p class="text-sm font-medium text-slate-700 mb-2">Update Status:</p>
            <div class="flex gap-2">
                @if($purchaseOrder->status === 'Draft')
                <form method="POST" action="{{ route('purchase-orders.status', $purchaseOrder) }}">
                    @csrf
                    <input type="hidden" name="status" value="Confirmed">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        ✓ Confirm Order
                    </button>
                </form>
                @endif
                @if($purchaseOrder->status === 'Confirmed')
                <form method="POST" action="{{ route('purchase-orders.status', $purchaseOrder) }}">
                    @csrf
                    <input type="hidden" name="status" value="Received">
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                        ✓ Mark as Received
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('purchase-orders.status', $purchaseOrder) }}">
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

        {{-- Back --}}
        <div class="mt-4">
            <a href="{{ route('purchase-orders.index') }}"
               class="text-sm text-blue-600 hover:underline">← Back to Purchase Orders</a>
        </div>
    </div>
</div>
@endsection