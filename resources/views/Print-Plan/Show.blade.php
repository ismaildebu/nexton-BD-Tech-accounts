@extends('layouts.app')

@section('page-title', 'Print Plan — ' . $plan->publication->name)
@section('page-subtitle', $plan->plan_date->format('d M Y'))

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $plan->publication->name }}</h2>
                <p class="text-sm text-slate-500">Plan date: {{ $plan->plan_date->format('d M Y') }}</p>
            </div>
            <span @class([
                'px-2 py-1 rounded-full text-xs font-medium',
                'bg-amber-50 text-amber-700' => $plan->status === 'Draft',
                'bg-blue-50 text-blue-700' => $plan->status === 'Submitted',
                'bg-green-50 text-green-700' => $plan->status === 'Approved',
                'bg-red-50 text-red-700' => $plan->status === 'Rejected',
            ])>{{ $plan->status }}</span>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div><dt class="text-slate-500">Previous Distribution</dt><dd class="font-medium">{{ number_format($plan->previous_distribution_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Average Distribution</dt><dd class="font-medium">{{ number_format($plan->average_distribution_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Expected Paid Qty</dt><dd class="font-medium">{{ number_format($plan->expected_paid_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Expected Free Qty</dt><dd class="font-medium">{{ number_format($plan->expected_free_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Expected Total</dt><dd class="font-medium">{{ number_format($plan->expected_total_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Buffer Qty</dt><dd class="font-medium">{{ number_format($plan->buffer_quantity) }}</dd></div>
            <div class="col-span-2 bg-slate-50 rounded-lg p-3">
                <dt class="text-slate-500">System Recommended Quantity</dt>
                <dd class="font-semibold text-lg">{{ number_format($plan->recommended_quantity) }}</dd>
            </div>
            @if($plan->adjusted_quantity !== null)
            <div class="col-span-2 bg-green-50 rounded-lg p-3">
                <dt class="text-slate-600">Approved / Adjusted Quantity</dt>
                <dd class="font-semibold text-lg text-green-700">{{ number_format($plan->adjusted_quantity) }}</dd>
                @if($plan->adjustment_reason)
                    <p class="text-xs text-slate-500 mt-1">Reason: {{ $plan->adjustment_reason }}</p>
                @endif
            </div>
            @endif
        </dl>

        @if(in_array($plan->status, ['Draft', 'Submitted']))
        @can('approve', $plan)
        <div class="border-t pt-4 space-y-4">
            <form method="POST" action="{{ route('media.print-plans.approve', $plan) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Approved Quantity <span class="text-slate-400">(blank = use recommended)</span></label>
                        <input type="number" name="adjusted_quantity" min="0" value="{{ old('adjusted_quantity') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Reason <span class="text-slate-400">(required if different from recommended)</span></label>
                        <input type="text" name="adjustment_reason" value="{{ old('adjustment_reason') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                    Approve Plan
                </button>
            </form>

            <form method="POST" action="{{ route('media.print-plans.reject', $plan) }}" class="flex gap-2 items-center">
                @csrf
                <input type="text" name="reason" placeholder="Rejection reason" required
                       class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <button type="submit" class="bg-red-50 text-red-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-100">
                    Reject
                </button>
            </form>
        </div>
        @endcan
        @endif

        @if($plan->isApproved())
            @can('create', \App\Models\PrintOrder::class)
            @if($plan->printOrders->isEmpty())
            <div class="border-t pt-4">
                <a href="{{ route('media.print-orders.create') }}?plan={{ $plan->id }}"
                   class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Create Print Order for {{ number_format($plan->final_quantity) }} copies
                </a>
            </div>
            @else
            <div class="border-t pt-4 text-sm text-slate-500">
                Print order already created:
                <a href="{{ route('media.print-orders.show', $plan->printOrders->first()) }}" class="text-blue-600 hover:underline">
                    {{ $plan->printOrders->first()->order_number }}
                </a>
            </div>
            @endif
            @endcan
        @endif

        <div class="pt-4">
            <a href="{{ route('media.print-plans.index') }}" class="text-slate-500 text-sm">Back to list</a>
        </div>
    </div>
</div>
@endsection