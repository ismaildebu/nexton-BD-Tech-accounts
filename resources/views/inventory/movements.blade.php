@extends('layouts.app')
@section('title', 'Stock Movements')
@section('page-title', 'Stock Movements')
@section('page-subtitle', 'All stock in/out history')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Stock Movement History</h2>
        <div class="flex gap-2">
            <a href="{{ route('inventory.stock-in') }}"
               class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                + Stock In
            </a>
            <a href="{{ route('inventory.stock-out') }}"
               class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">
                - Stock Out
            </a>
        </div>
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
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-left px-4 py-3 font-medium">Product</th>
                    <th class="text-left px-4 py-3 font-medium">Warehouse</th>
                    <th class="text-left px-4 py-3 font-medium">Type</th>
                    <th class="text-right px-4 py-3 font-medium">Quantity</th>
                    <th class="text-right px-4 py-3 font-medium">Unit Cost</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($movements as $m)
                @php
                    $typeColors = [
                        'in'          => 'bg-green-100 text-green-700',
                        'out'         => 'bg-orange-100 text-orange-700',
                        'transfer'    => 'bg-blue-100 text-blue-700',
                        'adjustment'  => 'bg-purple-100 text-purple-700',
                    ];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">{{ $m->movement_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium">{{ $m->product->name }}</td>
                    <td class="px-4 py-3">{{ $m->warehouse->name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $typeColors[$m->type] ?? '' }}">
                            {{ strtoupper($m->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium
                        {{ $m->type === 'in' ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $m->type === 'in' ? '+' : '-' }}{{ number_format($m->quantity, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($m->unit_cost, 2) }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($m->total_cost, 2) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $m->reference ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">No movements found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection