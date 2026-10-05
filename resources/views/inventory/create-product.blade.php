@extends('layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')
@section('page-subtitle', 'Create a new inventory product')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Product Information</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.products.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">

                {{-- Product Name --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Product Name *
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- SKU --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        SKU
                    </label>
                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku') }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Category
                    </label>
                    <input
                        type="text"
                        name="category"
                        value="{{ old('category') }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Unit --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Unit *
                    </label>
                    <select
                        name="unit"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="pcs" @selected(old('unit', 'pcs') === 'pcs')>
                            Pieces (pcs)
                        </option>
                        <option value="kg" @selected(old('unit') === 'kg')>
                            Kilogram (kg)
                        </option>
                        <option value="ltr" @selected(old('unit') === 'ltr')>
                            Liter (ltr)
                        </option>
                        <option value="box" @selected(old('unit') === 'box')>
                            Box
                        </option>
                        <option value="dozen" @selected(old('unit') === 'dozen')>
                            Dozen
                        </option>
                        <option value="meter" @selected(old('unit') === 'meter')>
                            Meter
                        </option>
                        <option value="set" @selected(old('unit') === 'set')>
                            Set
                        </option>
                    </select>
                </div>

                {{-- Reorder Level --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Reorder Level
                    </label>
                    <input
                        type="number"
                        name="reorder_level"
                        value="{{ old('reorder_level', 0) }}"
                        min="0"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Purchase Price --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Purchase Price (৳) *
                    </label>
                    <input
                        type="number"
                        name="purchase_price"
                        value="{{ old('purchase_price', 0) }}"
                        step="0.01"
                        min="0"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Sale Price --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Sale Price (৳) *
                    </label>
                    <input
                        type="number"
                        name="sale_price"
                        value="{{ old('sale_price', 0) }}"
                        step="0.01"
                        min="0"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Inventory Account --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Inventory Account *
                    </label>

                    <select
                        name="inventory_account_id"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="">-- Select Inventory Account --</option>

                        @foreach($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected((string) old('inventory_account_id') === (string) $account->id)
                            >
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-slate-500 mt-1">
                        Select the asset account where this product's inventory value will be recorded.
                    </p>
                </div>

                {{-- Description --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Description
                    </label>
                    <textarea
                        name="description"
                        rows="2"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Save Product
                </button>

                <a
                    href="{{ route('inventory.products') }}"
                    class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection