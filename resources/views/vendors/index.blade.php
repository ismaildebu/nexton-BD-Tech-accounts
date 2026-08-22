
@extends('layouts.app')

@section('page-title', 'Vendors')
@section('page-subtitle', 'Manage your suppliers and vendors')

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Vendors</h2>
        <a href="{{ route('vendors.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + Add Vendor
        </a>
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Name</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Phone</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Email</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Balance</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($vendors as $vendor)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $vendor->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $vendor->email ?? '-' }}</td>
                    <td class="px-4 py-3">৳{{ number_format($vendor->opening_balance, 2) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
<a href="{{ route('vendors.show', $vendor) }}"
   class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">
    View
</a>

                            <a href="{{ route('vendors.edit', $vendor) }}"
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600">Edit</a>
                            <form method="POST" action="{{ route('vendors.destroy', $vendor) }}"
                                  onsubmit="return confirm('Delete this vendor?')">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                        No vendors found. Add your first vendor!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection