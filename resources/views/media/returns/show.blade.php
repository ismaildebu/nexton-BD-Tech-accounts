@extends('layouts.app')
@section('page-title', 'Return Detail')
@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <span class="text-slate-500">Publication:</span>
                <span class="font-medium ml-1">{{ $return->publication->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500">Return Date:</span>
                <span class="font-medium ml-1">{{ $return->return_date }}</span>
            </div>
            <div>
                <span class="text-slate-500">Status:</span>
                <span class="ml-1 px-2 py-1 rounded-full text-xs font-medium
                    {{ $return->status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-green-50 text-green-700' }}">
                    {{ ucfirst($return->status) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500">Total Paid Return:</span>
                <span class="font-medium ml-1">{{ number_format($return->total_paid_return ?? 0) }}</span>
            </div>
            <div>
                <span class="text-slate-500">Total Free Return:</span>
                <span class="font-medium ml-1">{{ number_format($return->total_free_return ?? 0) }}</span>
            </div>
            @if($return->notes)
            <div class="col-span-2">
                <span class="text-slate-500">Notes:</span>
                <span class="ml-1">{{ $return->notes }}</span>
            </div>
            @endif
        </div>

        <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Party</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Paid Return</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Free Return</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Total Return</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($return->items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $item->party->name ?? $item->party_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->paid_return_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->free_return_quantity) }}</td>
                    <td class="px-4 py-3 text-right font-medium">
                        {{ number_format($item->paid_return_quantity + $item->free_return_quantity) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No items.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 border-t">
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-600">Totals</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($return->total_paid_return ?? 0) }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($return->total_free_return ?? 0) }}</td>
                    <td class="px-4 py-3 text-right font-medium">
                        {{ number_format(($return->total_paid_return ?? 0) + ($return->total_free_return ?? 0)) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="pt-4 border-t mt-4">
            <a href="{{ route('media.returns.index') }}" class="text-blue-600 hover:underline text-sm">
                ← Back to Returns
            </a>
        </div>
    </div>
</div>
@endsection
