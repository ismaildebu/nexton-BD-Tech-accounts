@extends('layouts.app')

@section('title', 'New Purchase Order')
@section('page-title', 'New Purchase Order')
@section('page-subtitle', 'Create a new purchase order')

@section('content')
<div class="max-w-4xl mx-auto" x-data="poForm()">
    <div class="bg-white rounded-xl shadow-sm p-6">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-6">
            @csrf

            {{-- Basic Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vendor *</label>
                    <select name="vendor_id" required
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Select Vendor --</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Order Date *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expected Delivery</label>
                    <input type="date" name="expected_date" value="{{ old('expected_date') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Items --}}
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-slate-700">Items</h3>
                    <button type="button" @click="addItem()"
                            class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700">
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border rounded-lg overflow-hidden">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium">Item Name *</th>
                                <th class="text-left px-3 py-2 font-medium">Description</th>
                                <th class="text-left px-3 py-2 font-medium w-20">Qty *</th>
                                <th class="text-left px-3 py-2 font-medium w-20">Unit</th>
                                <th class="text-left px-3 py-2 font-medium w-28">Price *</th>
                                <th class="text-left px-3 py-2 font-medium w-28">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="border-t">
                                    <td class="px-2 py-2">
                                        <input type="text" :name="`items[${index}][item_name]`"
                                               x-model="item.item_name" required
                                               class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" :name="`items[${index}][description]`"
                                               x-model="item.description"
                                               class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" :name="`items[${index}][quantity]`"
                                               x-model="item.quantity" @input="calcTotal(index)"
                                               min="0.01" step="0.01" required
                                               class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" :name="`items[${index}][unit]`"
                                               x-model="item.unit" placeholder="pcs"
                                               class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" :name="`items[${index}][unit_price]`"
                                               x-model="item.unit_price" @input="calcTotal(index)"
                                               min="0" step="0.01" required
                                               class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-2 py-2">
                                        <span class="font-medium" x-text="'৳' + parseFloat(item.total || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="px-2 py-2">
                                        <button type="button" @click="removeItem(index)"
                                                class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Subtotal:</span>
                        <span class="font-medium" x-text="'৳' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600">Tax (৳):</span>
                        <input type="number" name="tax" x-model="tax" @input="calcGrandTotal()"
                               min="0" step="0.01" value="0"
                               class="w-28 border border-slate-300 rounded px-2 py-1 text-sm text-right">
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600">Discount (৳):</span>
                        <input type="number" name="discount" x-model="discount" @input="calcGrandTotal()"
                               min="0" step="0.01" value="0"
                               class="w-28 border border-slate-300 rounded px-2 py-1 text-sm text-right">
                    </div>
                    <div class="flex justify-between text-base font-bold border-t pt-2">
                        <span>Total:</span>
                        <span x-text="'৳' + grandTotal.toFixed(2)"></span>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Create Purchase Order
                </button>
                <a href="{{ route('purchase-orders.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function poForm() {
    return {
        items: [{ item_name: '', description: '', quantity: 1, unit: '', unit_price: 0, total: 0 }],
        tax: 0,
        discount: 0,
        subtotal: 0,
        grandTotal: 0,

        addItem() {
            this.items.push({ item_name: '', description: '', quantity: 1, unit: '', unit_price: 0, total: 0 });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.calcGrandTotal();
            }
        },

        calcTotal(index) {
            const item = this.items[index];
            item.total = parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
            this.calcGrandTotal();
        },

        calcGrandTotal() {
            this.subtotal = this.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
            this.grandTotal = this.subtotal + parseFloat(this.tax || 0) - parseFloat(this.discount || 0);
        }
    }
}
</script>
@endpush
@endsection