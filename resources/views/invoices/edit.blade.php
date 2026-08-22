@extends('layouts.app')
@section('title', 'Edit Invoice')
@section('page-title', 'Edit Invoice')
@section('page-subtitle', $invoice->invoice_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Invoice Information</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                    <select name="customer_id" required
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice->customer_id) == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Date *</label>
                    <input type="date" name="invoice_date"
                           value="{{ old('invoice_date', optional($invoice->invoice_date)->toDateString()) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount (৳) *</label>
                    <input type="number" name="total_amount" value="{{ old('total_amount', $invoice->total_amount) }}"
                           step="0.01" min="0" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Paid Amount (৳) *</label>
                    <input type="number" name="paid_amount" value="{{ old('paid_amount', $invoice->paid_amount) }}"
                           step="0.01" min="0" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-slate-400 mt-1">Status is set to "Paid" automatically once paid amount reaches the total.</p>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Update Invoice
                </button>
                <a href="{{ route('invoices.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection