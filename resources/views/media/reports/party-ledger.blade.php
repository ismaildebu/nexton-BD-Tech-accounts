@extends('layouts.app')
@section('page-title', 'Party Ledger')
@section('page-subtitle', 'Distribution, return and collection history per party')
@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('media.reports.party-ledger') }}" class="grid grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Party</label>
                <select name="media_party_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select party...</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}" {{ request('media_party_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->type }})
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
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Show</button>
                @if($party)
                    <a href="{{ route('media.reports.party-ledger.pdf', request()->all()) }}"
                       class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700">PDF</a>
                @endif
            </div>
        </form>
    </div>

    @if($party)
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-4 gap-4 mb-2">
            <div>
                <div class="text-xs text-slate-500">Party</div>
                <div class="font-bold text-slate-800">{{ $party->name }}</div>
                <div class="text-xs text-slate-500">{{ ucfirst($party->type) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Distributed</div>
                <div class="text-xl font-bold text-blue-600">{{ number_format($totals['distributed']) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Returned</div>
                <div class="text-xl font-bold text-orange-600">{{ number_format($totals['returned']) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Outstanding Balance</div>
                <div class="text-xl font-bold {{ $totals['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($totals['balance'], 2) }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 pt-3 border-t">
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Sales Amount</div>
                <div class="text-lg font-bold text-slate-800">{{ number_format($totals['amount'], 2) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Collected</div>
                <div class="text-lg font-bold text-green-600">{{ number_format($totals['collected'], 2) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-slate-500 mb-1">Net Still Outstanding</div>
                <div class="text-lg font-bold {{ $totals['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($totals['balance'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Publication</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Copies Out</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Returned</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Debit</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Credit</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Ref</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($ledgerLines as $line)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2">{{ is_string($line->date) ? $line->date : $line->date?->format('d M Y') }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $line->type === 'Distribution' ? 'bg-blue-50 text-blue-700' : ($line->type === 'Return' ? 'bg-orange-50 text-orange-700' : 'bg-green-50 text-green-700') }}">
                            {{ $line->type }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-slate-600">{{ $line->publication }}</td>
                    <td class="px-4 py-2 text-right">{{ $line->total > 0 ? number_format($line->total) : '—' }}</td>
                    <td class="px-4 py-2 text-right text-orange-600">{{ $line->returned > 0 ? number_format($line->returned) : '—' }}</td>
                    <td class="px-4 py-2 text-right text-slate-800">{{ $line->dr_amount > 0 ? number_format($line->dr_amount, 2) : '—' }}</td>
                    <td class="px-4 py-2 text-right text-green-600">{{ $line->cr_amount > 0 ? number_format($line->cr_amount, 2) : '—' }}</td>
                    <td class="px-4 py-2 text-slate-400 text-xs">{{ $line->ref }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
