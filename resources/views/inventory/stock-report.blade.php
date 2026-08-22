@extends('layouts.app')
@section('title', 'Stock Report')
@section('page-title', 'Stock Report')
@section('page-subtitle', 'Current stock valuation')

@section('content')
<div class="space-y-4">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-medium">Total Products</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $products->count() }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-medium">Total Stock Value</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">৳{{ number_format($total_value, 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-medium">Warehouses</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $warehouses->count() }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-medium">Low Stock Items</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $low_stock->count() }}</p>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if($low_stock->count() > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="font-semibold text-red-700 mb-2">⚠️ Low Stock Items</p>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-red-600">
                    <th class="py-1">Product</th>
                    <th class="py-1 text-right">Current Stock</th>
                    <th class="py-1 text-right">Reorder Level</th>
                </tr>
            </thead>
            <tbody>
                @foreach($low_stock as $p)
                <tr class="border-t border-red-100">
                    <td class="py-2">{{ $p->name }}</td>
                    <td class="py-2 text-right font-medium text-red-700">{{ number_format($p->totalStock(), 2) }} {{ $p->unit }}</td>
                    <td class="py-2 text-right text-red-600">{{ $p->reorder_level }} {{ $p->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Stock by Product --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b">
            <h3 class="font-semibold">Stock by Product</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Product</th>
                    <th class="text-left px-4 py-3 font-medium">Category</th>
                    <th class="text-left px-4 py-3 font-medium">Unit</th>
                    <th class="text-right px-4 py-3 font-medium">Buy Price</th>
                    @foreach($warehouses as $w)
                    <th class="text-right px-4 py-3 font-medium">{{ $w->name }}</th>
                    @endforeach
                    <th class="text-right px-4 py-3 font-medium">Total Stock</th>
                    <th class="text-right px-4 py-3 font-medium">Total Value</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($products as $product)
                <tr class="hover:bg-slate-50 {{ $product->isLowStock() && $product->reorder_level > 0 ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $product->category ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $product->unit }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($product->purchase_price, 2) }}</td>
                    @foreach($warehouses as $w)
                    <td class="px-4 py-3 text-right">
                        {{ number_format($product->stockInWarehouse($w->id), 2) }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-medium
                        {{ $product->isLowStock() && $product->reorder_level > 0 ? 'text-red-600' : '' }}">
                        {{ number_format($product->totalStock(), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium">৳{{ number_format($product->stockValue(), 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 5 + $warehouses->count() }}" class="px-4 py-8 text-center text-slate-400">
                        No products found.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t bg-slate-50">
                <tr>
                    <td colspan="{{ 4 + $warehouses->count() }}" class="px-4 py-3 text-right font-bold">Total Value:</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">৳{{ number_format($total_value, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection