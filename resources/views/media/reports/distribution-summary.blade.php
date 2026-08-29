@extends('layouts.app')
@section('page-title', 'Distribution Summary')
@section('page-subtitle', 'Daily distribution records by date range')
@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('media.reports.distribution-summary') }}" class="grid grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Publication</label>
                <select name="publication_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Publications</option>
                    @foreach($publications as $pub)
                        <option value="{{ $pub->id }}" {{ request('publication_id') == $pub->id ? 'selected' : '' }}>{{ $pub->name }}</option>
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
                @if($distributions->isNotEmpty())
                    <a href="{{ route('media.reports.distribution-summary.pdf', request()->all()) }}"
                       class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700">PDF</a>
                @endif
            </div>
        </form>
    </div>

    @if($distributions->isNotEmpty())
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Runs</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($distributions->count()) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Paid Copies</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($totals['paid']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Free Copies</div>
            <div class="text-2xl font-bold text-slate-600">{{ number_format($totals['free']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="text-xs text-slate-500 mb-1">Total Amount</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totals['amount'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Publication</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Paid</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Total</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Amount</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($distributions as $dist)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2">{{ $dist->distribution_date?->format('d M Y') }}</td>
                    <td class="px-4 py-2 font-medium">{{ $dist->publication?->name }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($dist->total_paid_quantity) }}</td>
                    <td class="px-4 py-2 text-right text-slate-500">{{ number_format($dist->total_free_quantity) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($dist->total_quantity) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($dist->total_amount, 2) }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('media.distributions.show', $dist) }}" class="text-blue-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 border-t font-semibold">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right">Totals</td>
                    <td class="px-4 py-3 text-right">{{ number_format($totals['paid']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($totals['free']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($totals['total']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($totals['amount'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @elseif(request()->filled('from_date') || request()->filled('publication_id'))
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-slate-500">No distributions found for the selected filters.</div>
    @endif
</div>
@endsection
