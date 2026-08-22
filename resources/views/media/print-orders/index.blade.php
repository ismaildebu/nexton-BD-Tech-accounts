@extends('layouts.app')

@section('page-title', 'Print Orders')
@section('page-subtitle', 'Orders placed with the printing press')

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Print Orders</h2>
        @can('media-print-orders.create')
        <a href="{{ route('media.print-orders.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Print Order
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Order #</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Publication</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Press</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Order Date</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Ordered</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Printed</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Received</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $order->order_number }}</td>
                    <td class="px-4 py-3">{{ $order->publication->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->vendor->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->order_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($order->ordered_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($order->printed_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($order->received_quantity) }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-amber-50 text-amber-700' => $order->status === 'Draft',
                            'bg-blue-50 text-blue-700' => $order->status === 'Ordered',
                            'bg-indigo-50 text-indigo-700' => $order->status === 'Printing',
                            'bg-purple-50 text-purple-700' => $order->status === 'Printed',
                            'bg-green-50 text-green-700' => $order->status === 'Received',
                            'bg-red-50 text-red-700' => $order->status === 'Cancelled',
                        ])>{{ $order->status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.print-orders.show', $order) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-6 text-center text-slate-500">No print orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
