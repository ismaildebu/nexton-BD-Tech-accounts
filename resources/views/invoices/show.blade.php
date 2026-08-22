@extends('layouts.app')
@section('title', 'Invoice Details')
@section('page-title', 'Invoice Details')
@section('page-subtitle', $invoice->invoice_number)

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-2xl font-bold">{{ $invoice->invoice_number }}</h3>
                <p class="text-slate-500">{{ $invoice->customer->name ?? 'Walk-in Customer' }}</p>
            </div>

            <div class="flex items-center gap-2">
                @if($invoice->status === 'paid')
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                @elseif($invoice->is_overdue)
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Unpaid</span>
                @endif

                <a href="{{ route('invoices.edit', $invoice) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">
                    Edit
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Invoice Date</p>
                <p class="font-medium">{{ optional($invoice->invoice_date)->format('d M Y') ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Due Date</p>
                <p class="font-medium">{{ optional($invoice->due_date)->format('d M Y') ?? '-' }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Total Amount</p>
                <p class="font-medium">৳{{ number_format($invoice->total_amount, 2) }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
                <p class="text-xs text-slate-500">Paid Amount</p>
                <p class="font-medium">৳{{ number_format($invoice->paid_amount, 2) }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg col-span-2">
                <p class="text-xs text-slate-500">Due</p>
                <p class="font-medium text-lg">৳{{ number_format($invoice->due_amount, 2) }}</p>
            </div>
        </div>

        <a href="{{ route('invoices.index') }}"
           class="inline-block mt-6 text-sm text-blue-600 hover:underline">
            ← Back to Invoices
        </a>
    </div>

</div>
@endsection