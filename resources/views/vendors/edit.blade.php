@extends('layouts.app')

@section('page-title', 'Edit Vendor')
@section('page-subtitle', 'Update vendor information')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Edit Vendor</h2>

        <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vendor Name *</label>
                <input type="text" name="name" value="{{ old('name', $vendor->name) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <textarea name="address" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('address', $vendor->address) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trade License</label>
                    <input type="text" name="trade_license" value="{{ old('trade_license', $vendor->trade_license) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin', $vendor->tin) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Opening Balance (৳)</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', $vendor->opening_balance) }}" step="0.01"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Balance Type</label>
                    <select name="balance_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="Payable" {{ $vendor->balance_type == 'Payable' ? 'selected' : '' }}>Payable</option>
                        <option value="Advance" {{ $vendor->balance_type == 'Advance' ? 'selected' : '' }}>Advance</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Update Vendor
                </button>
                <a href="{{ route('vendors.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection