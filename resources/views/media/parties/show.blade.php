@extends('layouts.app')

@section('page-title', $party->name)
@section('page-subtitle', ucfirst($party->type) . ' details')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $party->name }}</h2>
                <p class="text-sm text-slate-500">{{ $party->code }} &middot; {{ $party->area ?? '—' }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $party->isAgent() ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                {{ ucfirst($party->type) }}
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500">Phone</dt>
                <dd class="font-medium">{{ $party->phone ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Alternate Phone</dt>
                <dd class="font-medium">{{ $party->alternate_phone ?? '-' }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-slate-500">Address</dt>
                <dd class="font-medium">{{ $party->address ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Opening Balance</dt>
                <dd class="font-medium">{{ number_format($party->opening_balance, 2) }} ({{ $party->balance_type }})</dd>
            </div>
            <div>
                <dt class="text-slate-500">Free % Override</dt>
                <dd class="font-medium">{{ $party->free_percentage !== null ? number_format($party->free_percentage, 2) . '%' : '— (falls through to publication/system default)' }}</dd>
            </div>
        </dl>

        <div class="flex gap-3 pt-6">
            @can('update', $party)
            <a href="{{ route('media.parties.edit', $party) }}"
               class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                Edit
            </a>
            @endcan
            <a href="{{ route('media.parties.index') }}" class="text-slate-500 px-4 py-2 text-sm">Back to list</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-700">Effective Free % by Publication</h3>
            <p class="text-xs text-slate-500">Party override &rarr; Publication default &rarr; System default</p>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-slate-600">Publication</th>
                    <th class="text-right px-4 py-2 font-medium text-slate-600">Effective Free %</th>
                    <th class="text-left px-4 py-2 font-medium text-slate-600">Source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($effectiveFreePercentages as $row)
                <tr>
                    <td class="px-4 py-2">{{ $row['publication']->name }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($row['percentage'], 2) }}%</td>
                    <td class="px-4 py-2 text-slate-500 capitalize">{{ $row['source'] }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-4 text-center text-slate-500">No active publications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
