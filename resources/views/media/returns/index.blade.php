@extends('layouts.app')
@section('page-title', 'Media Returns')
@section('page-subtitle', 'Unsold newspaper returns from agents and hawkers')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-slate-700">All Returns</h2>
        <a href="{{ route('media.returns.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Return
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Publication</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Paid Return</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free Return</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($returns as $return)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">{{ $return->return_date }}</td>
                    <td class="px-4 py-3 font-medium">{{ $return->publication->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($return->total_paid_return ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($return->total_free_return ?? 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $return->status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-green-50 text-green-700' }}">
                            {{ ucfirst($return->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.returns.show', $return) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No returns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
