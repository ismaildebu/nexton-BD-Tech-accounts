@extends('layouts.app')
@section('title', 'Warehouses')
@section('page-title', 'Warehouses')
@section('page-subtitle', 'Manage your warehouses')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Add Warehouse --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-4">Add Warehouse</h2>
        <form method="POST" action="{{ route('inventory.warehouses.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input type="text" name="name" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" name="location"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                Add Warehouse
            </button>
        </form>
    </div>

    {{-- Warehouse List --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Name</th>
                    <th class="text-left px-4 py-3 font-medium">Location</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($warehouses as $warehouse)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $warehouse->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $warehouse->location ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('inventory.warehouses.destroy', $warehouse) }}"
                              onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-slate-400">No warehouses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection