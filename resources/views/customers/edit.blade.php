@extends('layouts.app')
@section('title', 'Edit Customer')
@section('page-title', 'Edit Customer')
@section('page-subtitle', $customer->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Edit Customer</h2>

        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name *</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer Type</label>
                    <select name="customer_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="Individual" {{ $customer->customer_type === 'Individual' ? 'selected' : '' }}>Individual</option>
                        <option value="Business" {{ $customer->customer_type === 'Business' ? 'selected' : '' }}>Business</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Credit Limit (৳)</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" step="0.01"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                    <textarea name="address" rows="2"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trade License</label>
                    <input type="text" name="trade_license" value="{{ old('trade_license', $customer->trade_license) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin', $customer->tin) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Opening Balance (৳)</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', $customer->opening_balance) }}" step="0.01"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Balance Type</label>
                    <select name="balance_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="Receivable" {{ $customer->balance_type === 'Receivable' ? 'selected' : '' }}>Receivable</option>
                        <option value="Advance" {{ $customer->balance_type === 'Advance' ? 'selected' : '' }}>Advance</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Update Customer
                </button>
                <a href="{{ route('customers.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection