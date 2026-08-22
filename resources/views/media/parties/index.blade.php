@extends('layouts.app')

@section('page-title', 'Agents & Hawkers')
@section('page-subtitle', 'Media distribution parties — Agent and Hawker are independent')

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <div class="flex gap-2">
            <a href="{{ route('media.parties.index') }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request('type') ? 'bg-slate-100 text-slate-600' : 'bg-slate-800 text-white' }}">All</a>
            <a href="{{ route('media.parties.index', ['type' => 'agent']) }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request('type') === 'agent' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }}">Agents</a>
            <a href="{{ route('media.parties.index', ['type' => 'hawker']) }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request('type') === 'hawker' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }}">Hawkers</a>
        </div>
        @can('media-parties.create')
        <a href="{{ route('media.parties.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + Add Party
        </a>
        @endcan
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Code</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Phone</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Area</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free % Override</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($parties as $party)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $party->name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $party->isAgent() ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                            {{ ucfirst($party->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $party->code }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $party->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $party->area ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        {{ $party->free_percentage !== null ? number_format($party->free_percentage, 2) . '%' : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $party->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $party->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.parties.show', $party) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">No parties yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
