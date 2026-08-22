@extends('layouts.app')
@section('page-title', 'Distribution Detail')
@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <span class="text-slate-500">Publication:</span>
                <span class="font-medium ml-1">{{ $distribution->publication->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500">Date:</span>
                <span class="font-medium ml-1">{{ $distribution->distribution_date }}</span>
            </div>
            <div>
                <span class="text-slate-500">Status:</span>
                <span class="ml-1 px-2 py-1 rounded-full text-xs font-medium
                    {{ $distribution->status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-green-50 text-green-700' }}">
                    {{ ucfirst($distribution->status) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500">Total Amount:</span>
                <span class="font-medium ml-1">{{ number_format($distribution->total_amount, 2) }}</span>
            </div>
            @if($distribution->notes)
            <div class="col-span-2">
                <span class="text-slate-500">Notes:</span>
                <span class="ml-1">{{ $distribution->notes }}</span>
            </div>
            @endif
        </div>

        <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Party</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Paid Qty</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free %</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free Qty</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Total Qty</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Rate</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($distribution->items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $item->party->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->paid_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->free_percentage, 2) }}%</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->free_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->total_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No items.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 border-t">
                <tr>
                    <td colspan="4" class="px-4 py-3 font-medium text-slate-600">Totals</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($distribution->total_quantity) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($distribution->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="pt-4 border-t mt-4">
            <a href="{{ route('media.distributions.index') }}" class="text-blue-600 hover:underline text-sm">
                ← Back to Distributions
            </a>
        </div>
    </div>
</div>
@endsection
