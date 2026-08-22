@extends('layouts.app')
@section('page-title', 'Media Collections')
@section('page-subtitle', 'Cash collections from agents and hawkers')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-slate-700">All Collections</h2>
        <a href="{{ route('media.collections.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Collection
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Party</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Account</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Amount</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($collections as $collection)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">{{ $collection->collection_date }}</td>
                    <td class="px-4 py-3 font-medium">{{ $collection->party->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $collection->account->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($collection->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.collections.show', $collection) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No collections yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
