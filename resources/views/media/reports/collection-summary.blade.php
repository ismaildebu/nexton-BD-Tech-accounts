@extends('layouts.app')
@section('page-title', 'Collection Summary')
@section('page-subtitle', 'Payment collection records by date range')
@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('media.reports.collection-summary') }}" class="grid grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Party</label>
                <select name="media_party_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Parties</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" {{ request('media_party_id') == $party->id ? 'selected' : '' }}>
                            {{ $party->name }} ({{ $party->type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Show</button>
            </div>
        </form>
    </div>

    @if($collections->isNotEmpty())
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Collections</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($collections->count()) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Total Collected</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totals['amount'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Party</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Method</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Account</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Reference</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($collections as $col)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2">{{ $col->collection_date?->format('d M Y') }}</td>
                    <td class="px-4 py-2 font-medium">{{ $col->party?->name }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $col->party?->type === 'agent' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ ucfirst($col->party?->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-slate-600">{{ $col->payment_method }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ $col->account?->name }}</td>
                    <td class="px-4 py-2 text-slate-500 text-xs">{{ $col->reference }}</td>
                    <td class="px-4 py-2 text-right font-medium text-green-700">{{ number_format($col->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 border-t font-semibold">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-right">Total</td>
                    <td class="px-4 py-3 text-right text-green-700">{{ number_format($totals['amount'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @elseif(request()->filled('from_date') || request()->filled('media_party_id'))
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-slate-500">No collections found for the selected filters.</div>
    @endif
</div>
@endsection
