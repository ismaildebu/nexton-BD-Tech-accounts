@extends('layouts.app')

@section('title', 'New Sales Order')

@section('page-title', 'New Sales Order')

@section('page-subtitle', 'Create a new sales order')

@section('content')

<div
    class="max-w-5xl mx-auto"
    x-data="soForm()"
>
    <div class="bg-white rounded-xl shadow-sm p-6">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('sales-orders.store') }}"
            class="space-y-6"
        >
            @csrf

            {{-- Order Information --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Customer --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Customer *
                    </label>

                    <select
                        name="customer_id"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="">-- Select Customer --</option>

                        @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}
                            >
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Warehouse --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Warehouse *
                    </label>

                    <select
                        name="warehouse_id"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="">-- Select Warehouse --</option>

                        @foreach($warehouses as $warehouse)
                            <option
                                value="{{ $warehouse->id }}"
                                {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}
                            >
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Order Date --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Order Date *
                    </label>

                    <input
                        type="date"
                        name="order_date"
                        value="{{ old('order_date', date('Y-m-d')) }}"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                {{-- Delivery Date --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Delivery Date
                    </label>

                    <input
                        type="date"
                        name="delivery_date"
                        value="{{ old('delivery_date') }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

            </div>

            {{-- Items --}}
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-slate-700">
                        Items
                    </h3>

                    <button
                        type="button"
                        @click="addItem()"
                        class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700"
                    >
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border rounded-lg overflow-hidden">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium min-w-48">
                                    Product *
                                </th>

                                <th class="text-left px-3 py-2 font-medium min-w-40">
                                    Description
                                </th>

                                <th class="text-left px-3 py-2 font-medium w-24">
                                    Qty *
                                </th>

                                <th class="text-left px-3 py-2 font-medium w-24">
                                    Unit
                                </th>

                                <th class="text-left px-3 py-2 font-medium w-32">
                                    Price *
                                </th>

                                <th class="text-left px-3 py-2 font-medium w-32">
                                    Total
                                </th>

                                <th class="w-10"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <template
                                x-for="(item, index) in items"
                                :key="item.key"
                            >
                                <tr class="border-t">

                                    {{-- Product --}}
                                    <td class="px-2 py-2">

                                        <select
                                            :name="`items[${index}][product_id]`"
                                            x-model="item.product_id"
                                            @change="productChanged(index)"
                                            required
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"
                                        >
                                            <option value="">
                                                -- Select Product --
                                            </option>

                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">
                                                    {{ $product->name }}

                                                    @if($product->sku)
                                                        ({{ $product->sku }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Keep item_name compatible with existing schema --}}
                                        <input
                                            type="hidden"
                                            :name="`items[${index}][item_name]`"
                                            x-model="item.item_name"
                                        >

                                    </td>

                                    {{-- Description --}}
                                    <td class="px-2 py-2">
                                        <input
                                            type="text"
                                            :name="`items[${index}][description]`"
                                            x-model="item.description"
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"
                                        >
                                    </td>

                                    {{-- Quantity --}}
                                    <td class="px-2 py-2">
                                        <input
                                            type="number"
                                            :name="`items[${index}][quantity]`"
                                            x-model="item.quantity"
                                            @input="calcTotal(index)"
                                            min="0.01"
                                            step="0.01"
                                            required
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"
                                        >
                                    </td>

                                    {{-- Unit --}}
                                    <td class="px-2 py-2">
                                        <input
                                            type="text"
                                            :name="`items[${index}][unit]`"
                                            x-model="item.unit"
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"
                                        >
                                    </td>

                                    {{-- Unit Price --}}
                                    <td class="px-2 py-2">
                                        <input
                                            type="number"
                                            :name="`items[${index}][unit_price]`"
                                            x-model="item.unit_price"
                                            @input="calcTotal(index)"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"
                                        >
                                    </td>

                                    {{-- Line Total --}}
                                    <td class="px-2 py-2">
                                        <span
                                            x-text="'৳' + parseFloat(item.total || 0).toFixed(2)"
                                            class="font-medium text-slate-700"
                                        ></span>
                                    </td>

                                    {{-- Remove --}}
                                    <td class="px-2 py-2 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-red-500 hover:text-red-700 font-bold text-lg"
                                            title="Remove item"
                                        >
                                            ×
                                        </button>
                                    </td>

                                </tr>
                            </template>
                        </tbody>

                    </table>
                </div>
            </div>

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-80 space-y-2">

                    {{-- Subtotal --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">
                            Subtotal:
                        </span>

                        <span
                            x-text="'৳' + subtotal.toFixed(2)"
                            class="font-medium"
                        ></span>
                    </div>

                    {{-- Tax Percentage --}}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600">
                            Tax (%):
                        </span>

                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="tax"
                                x-model="tax"
                                @input="calcGrandTotal()"
                                min="0"
                                max="100"
                                step="0.01"
                                class="w-24 border border-slate-300 rounded px-2 py-1 text-sm text-right"
                            >

                            <span class="text-slate-500">%</span>
                        </div>
                    </div>

                    {{-- Tax Amount --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">
                            Tax Amount:
                        </span>

                        <span
                            x-text="'৳' + taxAmount.toFixed(2)"
                            class="text-slate-600"
                        ></span>
                    </div>

                    {{-- Discount Percentage --}}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600">
                            Discount (%):
                        </span>

                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="discount"
                                x-model="discount"
                                @input="calcGrandTotal()"
                                min="0"
                                max="100"
                                step="0.01"
                                class="w-24 border border-slate-300 rounded px-2 py-1 text-sm text-right"
                            >

                            <span class="text-slate-500">%</span>
                        </div>
                    </div>

                    {{-- Discount Amount --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">
                            Discount Amount:
                        </span>

                        <span
                            x-text="'৳' + discountAmount.toFixed(2)"
                            class="text-slate-600"
                        ></span>
                    </div>

                    {{-- Grand Total --}}
                    <div class="flex justify-between font-bold text-base border-t pt-2">
                        <span>
                            Total:
                        </span>

                        <span
                            x-text="'৳' + grandTotal.toFixed(2)"
                        ></span>
                    </div>

                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Notes
                </label>

                <textarea
                    name="notes"
                    rows="2"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                >{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Create Sales Order
                </button>

                <a
                    href="{{ route('sales-orders.index') }}"
                    class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200"
                >
                    Cancel
                </a>

            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
function soForm() {
    return {
        productData: @json($products->values()),

        items: [
            {
                key: Date.now(),
                product_id: '',
                item_name: '',
                description: '',
                quantity: 1,
                unit: '',
                unit_price: 0,
                total: 0
            }
        ],

        tax: 0,
        discount: 0,

        subtotal: 0,
        taxAmount: 0,
        discountAmount: 0,
        grandTotal: 0,

        addItem() {
            this.items.push({
                key: Date.now() + Math.random(),
                product_id: '',
                item_name: '',
                description: '',
                quantity: 1,
                unit: '',
                unit_price: 0,
                total: 0
            });

            this.calcGrandTotal();
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.calcGrandTotal();
            }
        },

        productChanged(index) {
            const item = this.items[index];

            const product = this.productData.find(
                product =>
                    String(product.id) === String(item.product_id)
            );

            if (!product) {
                item.item_name = '';
                item.unit = '';
                item.unit_price = 0;

                this.calcTotal(index);

                return;
            }

            item.item_name = product.name || '';
            item.unit = product.unit || '';
            item.unit_price = parseFloat(
                product.sale_price || 0
            );

            this.calcTotal(index);
        },

        calcTotal(index) {
            const item = this.items[index];

            item.total =
                parseFloat(item.quantity || 0)
                *
                parseFloat(item.unit_price || 0);

            this.calcGrandTotal();
        },

        calcGrandTotal() {
            /*
             * Calculate subtotal from line totals.
             */
            this.subtotal = this.items.reduce(
                (sum, item) =>
                    sum + parseFloat(item.total || 0),
                0
            );

            /*
             * Tax and discount are percentages.
             *
             * Example:
             * subtotal = 75,000
             * tax = 10%       => 7,500
             * discount = 5%   => 3,750
             */
            this.taxAmount =
                this.subtotal
                *
                (
                    parseFloat(this.tax || 0)
                    / 100
                );

            this.discountAmount =
                this.subtotal
                *
                (
                    parseFloat(this.discount || 0)
                    / 100
                );

            this.grandTotal =
                this.subtotal
                + this.taxAmount
                - this.discountAmount;

            /*
             * Do not allow the displayed total to become negative.
             * Backend validation remains authoritative.
             */
            if (this.grandTotal < 0) {
                this.grandTotal = 0;
            }
        }
    };
}
</script>
@endpush

@endsection