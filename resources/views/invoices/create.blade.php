@extends('layouts.app')
@section('title', 'New Invoice')
@section('page-title', 'New Invoice')
@section('page-subtitle', 'Create a new customer invoice')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Invoice Information</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                    <select name="customer_id" required
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($customers->isEmpty())
                        <p class="text-xs text-amber-600 mt-1">
                            No customers found. <a href="{{ route('customers.create') }}" class="underline">Add a customer first</a>.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Date *</label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->toDateString()) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount (৳) *</label>
                    <input type="number" name="total_amount" value="{{ old('total_amount') }}" step="0.01" min="0" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Invoice
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