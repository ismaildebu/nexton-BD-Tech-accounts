@extends('layouts.app')

@section('title', 'New Invoice')

@section('page-title', 'New Invoice')

@section('page-subtitle', 'Create a new customer invoice')

@section('content')

<div class="max-w-6xl mx-auto">

    <form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/>
                            <path d="M8 13h8"/>
                            <path d="M8 17h5"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            Invoice Information
                        </h2>
                        <p class="text-sm text-slate-500">
                            Create a new customer invoice
                        </p>
                    </div>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="mx-6 mt-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3">
                    <p class="text-sm font-semibold mb-1">
                        Please correct the following errors:
                    </p>

                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Invoice Information --}}
            <div class="p-6 space-y-6">

                {{-- Customer / Dates --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    {{-- Customer --}}
                    <div class="md:col-span-1">
                        <label
                            for="customer_id"
                            class="block text-sm font-medium text-slate-700 mb-2"
                        >
                            Customer <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="customer_id"
                            name="customer_id"
                            required
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm bg-white
                                   focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition"
                        >
                            <option value="">-- Select Customer --</option>

                            @foreach($customers as $customer)
                                <option
                                    value="{{ $customer->id }}"
                                    @selected(old('customer_id') == $customer->id)
                                >
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>

                        @if($customers->isEmpty())
                            <p class="text-xs text-amber-600 mt-2">
                                No customers found.
                                <a
                                    href="{{ route('customers.create') }}"
                                    class="underline font-medium"
                                >
                                    Add a customer first
                                </a>.
                            </p>
                        @endif
                    </div>

                    {{-- Invoice Date --}}
                    <div>
                        <label
                            for="invoice_date"
                            class="block text-sm font-medium text-slate-700 mb-2"
                        >
                            Invoice Date <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="invoice_date"
                            type="date"
                            name="invoice_date"
                            value="{{ old('invoice_date', now()->toDateString()) }}"
                            required
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm
                                   focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition"
                        >
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label
                            for="due_date"
                            class="block text-sm font-medium text-slate-700 mb-2"
                        >
                            Due Date
                        </label>

                        <input
                            id="due_date"
                            type="date"
                            name="due_date"
                            value="{{ old('due_date') }}"
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm
                                   focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition"
                        >
                    </div>

                </div>

                {{-- Items --}}
                <div class="border border-slate-200 rounded-2xl overflow-hidden">

                    {{-- Items Header --}}
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-800">
                                Items
                            </h3>

                            <p class="text-xs text-slate-500 mt-1">
                                Add the products or services included in this invoice.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="add-item"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                                   bg-blue-600 text-white text-sm font-medium
                                   hover:bg-blue-700 transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="h-4 w-4"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>

                            Add Item
                        </button>
                    </div>

                    {{-- Items Table --}}
                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[900px] text-sm">

                            <thead class="bg-white border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">

                                    <th class="px-4 py-3 min-w-[220px]">
                                        Product <span class="text-red-500">*</span>
                                    </th>

                                    <th class="px-4 py-3 min-w-[200px]">
                                        Description
                                    </th>

                                    <th class="px-4 py-3 w-24">
                                        Qty <span class="text-red-500">*</span>
                                    </th>

                                    <th class="px-4 py-3 w-24">
                                        Unit
                                    </th>

                                    <th class="px-4 py-3 w-32">
                                        Price <span class="text-red-500">*</span>
                                    </th>

                                    <th class="px-4 py-3 w-32 text-right">
                                        Total
                                    </th>

                                    <th class="px-4 py-3 w-12">
                                    </th>

                                </tr>
                            </thead>

                            <tbody id="items-body">

                                <tr class="item-row border-b border-slate-100">

                                    <td class="px-4 py-3">
                                        <select
                                            class="item-product w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white
                                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                        >
                                            <option value="">-- Select Product --</option>
                                        </select>
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            type="text"
                                            class="item-description w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                            placeholder="Description"
                                        >
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            class="item-qty w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                            value="1"
                                            min="0"
                                            step="0.01"
                                        >
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            type="text"
                                            class="item-unit w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                            placeholder="pcs"
                                        >
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            class="item-price w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                            value="0"
                                            min="0"
                                            step="0.01"
                                        >
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <span class="item-total font-semibold text-slate-800">
                                            ৳0.00
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="remove-item inline-flex h-8 w-8 items-center justify-center
                                                   rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition"
                                            title="Remove item"
                                        >
                                            ×
                                        </button>
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                    {{-- Summary --}}
                    <div class="border-t border-slate-200 bg-slate-50/70 px-5 py-5">

                        <div class="ml-auto w-full md:w-96 space-y-3">

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">
                                    Subtotal
                                </span>

                                <span
                                    id="subtotal"
                                    class="font-medium text-slate-800"
                                >
                                    ৳0.00
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-5">
                                <label
                                    for="tax"
                                    class="text-sm text-slate-600"
                                >
                                    Tax (৳)
                                </label>

                                <input
                                    id="tax"
                                    type="number"
                                    value="0"
                                    min="0"
                                    step="0.01"
                                    class="w-32 border border-slate-300 rounded-lg px-3 py-2 text-sm text-right
                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                >
                            </div>

                            <div class="flex items-center justify-between gap-5">
                                <label
                                    for="discount"
                                    class="text-sm text-slate-600"
                                >
                                    Discount (৳)
                                </label>

                                <input
                                    id="discount"
                                    type="number"
                                    value="0"
                                    min="0"
                                    step="0.01"
                                    class="w-32 border border-slate-300 rounded-lg px-3 py-2 text-sm text-right
                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-50"
                                >
                            </div>

                            <div class="border-t border-slate-300 pt-3 flex items-center justify-between">
                                <span class="text-base font-semibold text-slate-800">
                                    Total
                                </span>

                                <span
                                    id="grand-total"
                                    class="text-xl font-bold text-blue-600"
                                >
                                    ৳0.00
                                </span>
                            </div>

                            {{-- Existing backend field --}}
                            <input
                                type="hidden"
                                name="total_amount"
                                id="total_amount"
                                value="{{ old('total_amount', '0.00') }}"
                            >

                        </div>

                    </div>

                </div>

                {{-- Notes --}}
                <div>
                    <label
                        for="notes"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        rows="4"
                        class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition resize-y"
                        placeholder="Add any additional notes..."
                    ></textarea>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">

                    <a
                        href="{{ route('invoices.index') }}"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl
                               border border-slate-300 bg-white text-sm font-medium text-slate-700
                               hover:bg-slate-100 transition"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5
                               rounded-xl bg-blue-600 text-white text-sm font-semibold
                               hover:bg-blue-700 focus:outline-none focus:ring-4
                               focus:ring-blue-100 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-4 w-4"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>

                        Create Invoice
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsBody = document.getElementById('items-body');
    const addItemButton = document.getElementById('add-item');
    const subtotalElement = document.getElementById('subtotal');
    const grandTotalElement = document.getElementById('grand-total');
    const totalAmountInput = document.getElementById('total_amount');
    const taxInput = document.getElementById('tax');
    const discountInput = document.getElementById('discount');

    function calculateTotals() {
        let subtotal = 0;

        itemsBody.querySelectorAll('.item-row').forEach(function (row) {
            const quantity = parseFloat(
                row.querySelector('.item-qty')?.value || 0
            );

            const price = parseFloat(
                row.querySelector('.item-price')?.value || 0
            );

            const total = quantity * price;

            const totalElement = row.querySelector('.item-total');

            if (totalElement) {
                totalElement.textContent = '৳' + total.toFixed(2);
            }

            subtotal += total;
        });

        const tax = parseFloat(taxInput.value || 0);
        const discount = parseFloat(discountInput.value || 0);

        const grandTotal = Math.max(
            0,
            subtotal + tax - discount
        );

        subtotalElement.textContent = '৳' + subtotal.toFixed(2);
        grandTotalElement.textContent = '৳' + grandTotal.toFixed(2);

        totalAmountInput.value = grandTotal.toFixed(2);
    }

    function bindRow(row) {
        row.querySelectorAll('.item-qty, .item-price').forEach(function (input) {
            input.addEventListener('input', calculateTotals);
        });

        const removeButton = row.querySelector('.remove-item');

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                const rows = itemsBody.querySelectorAll('.item-row');

                if (rows.length > 1) {
                    row.remove();
                    calculateTotals();
                }
            });
        }
    }

    addItemButton.addEventListener('click', function () {
        const firstRow = itemsBody.querySelector('.item-row');

        if (!firstRow) {
            return;
        }

        const newRow = firstRow.cloneNode(true);

        newRow.querySelector('.item-product').value = '';
        newRow.querySelector('.item-description').value = '';
        newRow.querySelector('.item-qty').value = '1';
        newRow.querySelector('.item-unit').value = '';
        newRow.querySelector('.item-price').value = '0';
        newRow.querySelector('.item-total').textContent = '৳0.00';

        itemsBody.appendChild(newRow);

        bindRow(newRow);
    });

    taxInput.addEventListener('input', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    itemsBody.querySelectorAll('.item-row').forEach(function (row) {
        bindRow(row);
    });

    calculateTotals();
});
</script>

@endsection