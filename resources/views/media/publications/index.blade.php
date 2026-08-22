@extends('layouts.app')

@section('page-title', 'Publications')
@section('page-subtitle', 'Manage your newspapers/magazines')

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Publications</h2>
        @can('media-publications.create')
        <a href="{{ route('media.publications.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + Add Publication
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Code</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Type</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Selling Price</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Default Free %</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($publications as $publication)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $publication->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $publication->code }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $publication->publication_type ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($publication->selling_price, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        {{ $publication->default_free_percentage !== null ? number_format($publication->default_free_percentage, 2) . '%' : '— (system default)' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $publication->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $publication->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.publications.show', $publication) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No publications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
