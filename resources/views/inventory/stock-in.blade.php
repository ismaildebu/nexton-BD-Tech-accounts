@extends('layouts.app')
@section('title', 'Stock In')
@section('page-title', 'Stock In')
@section('page-subtitle', 'Record incoming stock')

@section('content')
<div class="max-w-4xl mx-auto" x-data="stockForm()">
    <div class="bg-white rounded-xl shadow-sm p-6">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.stock-in.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse *</label>
                    <select name="warehouse_id" required
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
                    <input type="date" name="movement_date" value="{{ date('Y-m-d') }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reference (PO No.)</label>
                    <input type="text" name="reference"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Items --}}
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-slate-700">Products</h3>
                    <button type="button" @click="addItem()"
                            class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700">
                        + Add Product
                    </button>
                </div>
                <table class="w-full text-sm border rounded-lg overflow-hidden">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-3 py-2 font-medium">Product *</th>
                            <th class="text-left px-3 py-2 font-medium w-28">Quantity *</th>
                            <th class="text-left px-3 py-2 font-medium w-32">Unit Cost (৳) *</th>
                            <th class="text-left px-3 py-2 font-medium w-28">Total</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-t">
                                <td class="px-2 py-2">
                                    <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                            class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                        <option value="">-- Select --</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" :name="`items[${index}][quantity]`"
                                           x-model="item.quantity" @input="calcTotal(index)"
                                           min="0.01" step="0.01" required
                                           class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" :name="`items[${index}][unit_cost]`"
                                           x-model="item.unit_cost" @input="calcTotal(index)"
                                           min="0" step="0.01" required
                                           class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                                </td>
                                <td class="px-2 py-2 font-medium">
                                    <span x-text="'৳' + parseFloat(item.total || 0).toFixed(2)"></span>
                                </td>
                                <td class="px-2 py-2">
                                    <button type="button" @click="removeItem(index)"
                                            class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t bg-slate-50">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right font-medium">Grand Total:</td>
                            <td class="px-3 py-2 font-bold text-green-600" x-text="'৳' + grandTotal.toFixed(2)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                    ✓ Record Stock In
                </button>
                <a href="{{ route('inventory.products') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function stockForm() {
    return {
        items: [{ product_id: '', quantity: 1, unit_cost: 0, total: 0 }],
        grandTotal: 0,
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_cost: 0, total: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) { this.items.splice(index, 1); this.calcGrandTotal(); }
        },
        calcTotal(index) {
            const item = this.items[index];
            item.total = parseFloat(item.quantity || 0) * parseFloat(item.unit_cost || 0);
            this.calcGrandTotal();
        },
        calcGrandTotal() {
            this.grandTotal = this.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
        }
    }
}
</script>
@endpush
@endsection