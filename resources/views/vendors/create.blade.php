@extends('layouts.app')

@section('page-title', 'Add Vendor')
@section('page-subtitle', 'Create a new vendor/supplier')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Vendor Information</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendors.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vendor Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <textarea name="address" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trade License</label>
                    <input type="text" name="trade_license" value="{{ old('trade_license') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Opening Balance (৳)</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', 0) }}" step="0.01"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Balance Type</label>
                    <select name="balance_type"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Payable">Payable (আমরা দেব)</option>
                        <option value="Advance">Advance (আমরা পাব)</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Vendor
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