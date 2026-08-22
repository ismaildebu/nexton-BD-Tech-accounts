@extends('layouts.app')

@section('page-title', 'Vendor Details')
@section('page-subtitle', $vendor->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    {{-- Vendor Info --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $vendor->name }}</h2>
                <span class="px-2 py-1 rounded-full text-xs font-medium mt-1 inline-block bg-purple-100 text-purple-700">
                    {{ $vendor->balance_type }}
                </span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('vendors.edit', $vendor) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">Edit</a>
                <a href="{{ route('purchase-orders.create', ['vendor_id' => $vendor->id]) }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">+ Purchase Order</a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Phone</p>
                <p class="font-medium">{{ $vendor->phone ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Email</p>
                <p class="font-medium text-sm">{{ $vendor->email ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Opening Balance</p>
                <p class="font-medium">৳{{ number_format($vendor->opening_balance, 2) }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">TIN</p>
                <p class="font-medium">{{ $vendor->tin ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Purchase Orders --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-slate-700 mb-4">Purchase Orders</h3>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">PO Number</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($vendor->purchaseOrders as $po)
                <tr>
                    <td class="px-4 py-3 font-medium text-blue-600">
                        <a href="{{ route('purchase-orders.show', $po) }}">{{ $po->po_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $po->order_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($po->total, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                            {{ $po->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">No purchase orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Purchase Bills --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-slate-700 mb-4">Purchase Bills</h3>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Bill Number</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-right px-4 py-3 font-medium">Due</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($vendor->purchaseBills as $bill)
                <tr>
                    <td class="px-4 py-3 font-medium text-blue-600">
                        <a href="{{ route('purchase-bills.show', $bill) }}">{{ $bill->bill_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $bill->bill_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($bill->total, 2) }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($bill->due_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                            {{ $bill->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No purchase bills found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('vendors.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Vendors</a>
</div>
@endsection