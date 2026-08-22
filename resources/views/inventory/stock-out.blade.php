@extends('layouts.app')
@section('title', 'Stock Out')
@section('page-title', 'Stock Out')
@section('page-subtitle', 'Record outgoing stock')

@section('content')
<div class="max-w-4xl mx-auto" x-data="stockOutForm()">
    <div class="bg-white rounded-xl shadow-sm p-6">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.stock-out.store') }}" class="space-y-6">
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
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reference (SO No.)</label>
                    <input type="text" name="reference"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-slate-700">Products</h3>
                    <button type="button" @click="addItem()"
                            class="bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-orange-700">
                        + Add Product
                    </button>
                </div>
                <table class="w-full text-sm border rounded-lg overflow-hidden">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-3 py-2 font-medium">Product *</th>
                            <th class="text-left px-3 py-2 font-medium w-32">Quantity *</th>
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
                                        <option value="{{ $p->id }}">
                                            {{ $p->name }} (Stock: {{ $p->totalStock() }} {{ $p->unit }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" :name="`items[${index}][quantity]`"
                                           x-model="item.quantity"
                                           min="0.01" step="0.01" required
                                           class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
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

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">
                    ✓ Record Stock Out
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
function stockOutForm() {
    return {
        items: [{ product_id: '', quantity: 1 }],
        addItem() { this.items.push({ product_id: '', quantity: 1 }); },
        removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); }
    }
}
</script>
@endpush
@endsection