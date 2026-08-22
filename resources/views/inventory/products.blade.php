@extends('layouts.app')
@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Manage your inventory products')

@section('content')
<div class="space-y-4">

    {{-- Low Stock Alert --}}
    @if($low_stock->count() > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="font-semibold text-red-700 mb-2">⚠️ Low Stock Alert ({{ $low_stock->count() }} products)</p>
        <div class="flex flex-wrap gap-2">
            @foreach($low_stock as $p)
            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">
                {{ $p->name }} ({{ $p->totalStock() }} {{ $p->unit }})
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Products</h2>
        <div class="flex gap-2">
            <a href="{{ route('inventory.stock-in') }}"
               class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                + Stock In
            </a>
            <a href="{{ route('inventory.stock-out') }}"
               class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">
                - Stock Out
            </a>
            <a href="{{ route('inventory.products.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                + Add Product
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Name</th>
                    <th class="text-left px-4 py-3 font-medium">SKU</th>
                    <th class="text-left px-4 py-3 font-medium">Category</th>
                    <th class="text-left px-4 py-3 font-medium">Unit</th>
                    <th class="text-right px-4 py-3 font-medium">Buy Price</th>
                    <th class="text-right px-4 py-3 font-medium">Sale Price</th>
                    <th class="text-right px-4 py-3 font-medium">Stock</th>
                    <th class="text-right px-4 py-3 font-medium">Value</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                @php $stock = $product->totalStock(); @endphp
                <tr class="hover:bg-slate-50 {{ $product->isLowStock() && $product->reorder_level > 0 ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $product->sku ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $product->category ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $product->unit }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($product->purchase_price, 2) }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($product->sale_price, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $product->isLowStock() && $product->reorder_level > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($stock, 2) }}
                        @if($product->isLowStock() && $product->reorder_level > 0)
                            <span class="text-xs">⚠️</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($product->stockValue(), 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('inventory.products.edit', $product) }}"
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600">Edit</a>
                            <form method="POST" action="{{ route('inventory.products.destroy', $product) }}"
                                  onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-slate-400">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('inventory.movements') }}"
           class="bg-white p-4 rounded-xl shadow-sm text-center hover:bg-slate-50">
            <p class="text-2xl mb-1">📋</p>
            <p class="text-sm font-medium">Stock Movements</p>
        </a>
        <a href="{{ route('inventory.stock-report') }}"
           class="bg-white p-4 rounded-xl shadow-sm text-center hover:bg-slate-50">
            <p class="text-2xl mb-1">📊</p>
            <p class="text-sm font-medium">Stock Report</p>
        </a>
        <a href="{{ route('inventory.warehouses') }}"
           class="bg-white p-4 rounded-xl shadow-sm text-center hover:bg-slate-50">
            <p class="text-2xl mb-1">🏭</p>
            <p class="text-sm font-medium">Warehouses</p>
        </a>
        <a href="{{ route('inventory.stock-in') }}"
           class="bg-white p-4 rounded-xl shadow-sm text-center hover:bg-slate-50">
            <p class="text-2xl mb-1">📦</p>
            <p class="text-sm font-medium">Stock In</p>
        </a>
    </div>
</div>
@endsection