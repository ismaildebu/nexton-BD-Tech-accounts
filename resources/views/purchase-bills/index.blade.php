@extends('layouts.app')

@section('title', 'Purchase Bills')
@section('page-title', 'Purchase Bills')
@section('page-subtitle', 'Manage your purchase bills')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Purchase Bills</h2>
        <a href="{{ route('purchase-bills.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Bill
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
                    <th class="text-left px-4 py-3 font-medium">Bill No</th>
                    <th class="text-left px-4 py-3 font-medium">Vendor</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-left px-4 py-3 font-medium">Due Date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-right px-4 py-3 font-medium">Paid</th>
                    <th class="text-right px-4 py-3 font-medium">Due</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($bills as $bill)
                @php
                    $colors = [
                        'Unpaid'  => 'bg-red-100 text-red-700',
                        'Partial' => 'bg-yellow-100 text-yellow-700',
                        'Paid'    => 'bg-green-100 text-green-700',
                    ];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-blue-600">
                        <a href="{{ route('purchase-bills.show', $bill) }}">{{ $bill->bill_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $bill->vendor->name }}</td>
                    <td class="px-4 py-3">{{ $bill->bill_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 {{ $bill->due_date && $bill->due_date->isPast() && $bill->status !== 'Paid' ? 'text-red-600 font-medium' : '' }}">
                        {{ $bill->due_date?->format('d M Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($bill->total, 2) }}</td>
                    <td class="px-4 py-3 text-right text-green-600">৳{{ number_format($bill->paid_amount, 2) }}</td>
                    <td class="px-4 py-3 text-right text-red-600">৳{{ number_format($bill->due_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$bill->status] ?? '' }}">
                            {{ $bill->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('purchase-bills.show', $bill) }}"
                               class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">View</a>
                            <form method="POST" action="{{ route('purchase-bills.destroy', $bill) }}"
                                  onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-slate-400">No bills found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection