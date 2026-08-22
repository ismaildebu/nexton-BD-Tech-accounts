@extends('layouts.app')
@section('title', 'Sales Orders')
@section('page-title', 'Sales Orders')
@section('page-subtitle', 'Manage your sales orders')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Sales Orders</h2>
        <a href="{{ route('sales-orders.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Sales Order
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">SO Number</th>
                    <th class="text-left px-4 py-3 font-medium">Customer</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-left px-4 py-3 font-medium">Delivery</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                @php
                    $colors = [
                        'Draft'     => 'bg-gray-100 text-gray-700',
                        'Confirmed' => 'bg-blue-100 text-blue-700',
                        'Delivered' => 'bg-green-100 text-green-700',
                        'Cancelled' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-blue-600">
                        <a href="{{ route('sales-orders.show', $order) }}">{{ $order->so_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $order->customer->name }}</td>
                    <td class="px-4 py-3">{{ $order->order_date->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $order->delivery_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-medium">৳{{ number_format($order->total, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$order->status] ?? '' }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('sales-orders.show', $order) }}"
                               class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">View</a>
                            <form method="POST" action="{{ route('sales-orders.destroy', $order) }}"
                                  onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">No sales orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection