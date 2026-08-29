@extends('layouts.app')
@section('page-title', 'Stock Report')
@section('page-subtitle', 'Newspaper stock movement history')
@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('media.reports.stock') }}" class="grid grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Publication</label>
                <select name="publication_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select...</option>
                    @foreach($publications as $pub)
                        <option value="{{ $pub->id }}" {{ request('publication_id') == $pub->id ? 'selected' : '' }}>
                            {{ $pub->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Show
                </button>
                @if($publication)
                    <a href="{{ route('media.reports.stock.pdf', request()->all()) }}"
                       class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700">
                        PDF
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($publication)
    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Movements</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($movements->count()) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Total In</div>
            <div class="text-2xl font-bold text-green-600">
                {{ number_format($movements->where('quantity', '>', 0)->sum('quantity')) }}
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Current Balance</div>
            <div class="text-2xl font-bold {{ $runningBalance < 0 ? 'text-red-600' : 'text-blue-600' }}">
                {{ number_format($runningBalance) }}
            </div>
        </div>
    </div>

    {{-- Movement Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b">
            <h3 class="font-semibold text-slate-700">{{ $publication->name }} — Stock Movements</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Type</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">In</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Out</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Balance</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($movements as $mov)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2">{{ $mov->movement_date?->format('d M Y') }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ in_array($mov->type, ['distribution','damage']) ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                            {{ ucfirst($mov->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right text-green-700">
                        {{ $mov->quantity > 0 ? number_format($mov->quantity) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-red-700">
                        {{ $mov->quantity < 0 ? number_format(abs($mov->quantity)) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right font-medium {{ $mov->running_balance < 0 ? 'text-red-600' : '' }}">
                        {{ number_format($mov->running_balance) }}
                    </td>
                    <td class="px-4 py-2 text-slate-500 text-xs">{{ $mov->notes }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No movements in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
