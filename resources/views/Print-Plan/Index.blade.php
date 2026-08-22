@extends('layouts.app')

@section('page-title', 'Print Planning')
@section('page-subtitle', 'Recommended print quantities based on distribution history')

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Print Plans</h2>
        @can('create', \App\Models\PrintPlan::class)
        <a href="{{ route('media.print-plans.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Plan
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
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Publication</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Plan Date</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Recommended</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-600">Approved Qty</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $plan->publication->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $plan->plan_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($plan->recommended_quantity) }}</td>
                    <td class="px-4 py-3 text-right">{{ $plan->adjusted_quantity !== null ? number_format($plan->adjusted_quantity) : '-' }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-amber-50 text-amber-700' => $plan->status === 'Draft',
                            'bg-blue-50 text-blue-700' => $plan->status === 'Submitted',
                            'bg-green-50 text-green-700' => $plan->status === 'Approved',
                            'bg-red-50 text-red-700' => $plan->status === 'Rejected',
                        ])>{{ $plan->status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('media.print-plans.show', $plan) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No print plans yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection