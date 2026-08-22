@extends('layouts.app')
@section('title', 'Customer Details')
@section('page-title', 'Customer Details')
@section('page-subtitle', $customer->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    {{-- Customer Info --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $customer->name }}</h2>
                <span class="px-2 py-1 rounded-full text-xs font-medium mt-1 inline-block
                    {{ $customer->customer_type === 'Business' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ $customer->customer_type }}
                </span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('customers.edit', $customer) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">Edit</a>
                <a href="{{ route('sales-orders.create', ['customer_id' => $customer->id]) }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">+ Sales Order</a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Phone</p>
                <p class="font-medium">{{ $customer->phone ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Email</p>
                <p class="font-medium text-sm">{{ $customer->email ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Credit Limit</p>
                <p class="font-medium">৳{{ number_format($customer->credit_limit, 2) }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Balance Type</p>
                <p class="font-medium">{{ $customer->balance_type }}</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-blue-50 rounded-xl text-center">
                <p class="text-xs text-blue-600 font-medium uppercase">Total Invoiced</p>
                <p class="text-xl font-bold text-blue-700 mt-1">৳{{ number_format($customer->totalInvoiced(), 2) }}</p>
            </div>
            <div class="p-4 bg-green-50 rounded-xl text-center">
                <p class="text-xs text-green-600 font-medium uppercase">Total Paid</p>
                <p class="text-xl font-bold text-green-700 mt-1">৳{{ number_format($customer->totalPaid(), 2) }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl text-center">
                <p class="text-xs text-red-600 font-medium uppercase">Total Due</p>
                <p class="text-xl font-bold text-red-700 mt-1">৳{{ number_format($customer->totalDue(), 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Invoices --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-slate-700">Invoices</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Invoice No</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-right px-4 py-3 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($customer->invoices as $invoice)
                <tr>
                    <td class="px-4 py-3 font-medium text-blue-600">
                        {{ $invoice->invoice_number ?? $invoice->id }}
                    </td>
                    <td class="px-4 py-3">{{ $invoice->invoice_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                            {{ $invoice->status ?? 'N/A' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">No invoices found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sales Orders --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-slate-700">Sales Orders</h3>
            <a href="{{ route('sales-orders.create') }}"
               class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700">
                + New SO
            </a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">SO Number</th>
                    <th class="text-left px-4 py-3 font-medium">Date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($customer->salesOrders as $so)
                @php
                    $colors = [
                        'Draft'     => 'bg-gray-100 text-gray-700',
                        'Confirmed' => 'bg-blue-100 text-blue-700',
                        'Delivered' => 'bg-green-100 text-green-700',
                        'Cancelled' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium text-blue-600">
                        <a href="{{ route('sales-orders.show', $so) }}">{{ $so->so_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $so->order_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($so->total, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$so->status] ?? '' }}">
                            {{ $so->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">No sales orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('customers.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Customers</a>
</div>
@endsection