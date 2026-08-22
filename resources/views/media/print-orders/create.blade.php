@extends('layouts.app')

@section('page-title', 'New Print Order')
@section('page-subtitle', $selectedPlan ? 'Creating from an approved print plan' : 'Ad-hoc print order')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if(!$selectedPlan && $approvedPlans->isNotEmpty())
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
        <p class="font-medium text-blue-900 mb-2">Approved print plans awaiting an order:</p>
        <ul class="space-y-1">
            @foreach($approvedPlans as $plan)
            <li>
                <a href="{{ route('media.print-orders.create') }}?plan={{ $plan->id }}" class="text-blue-700 hover:underline">
                    {{ $plan->publication->name }} — {{ $plan->plan_date->format('d M Y') }} ({{ number_format($plan->final_quantity) }} copies)
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Order Details</h2>

        @if($selectedPlan)
            {{-- FROM PLAN: ordered_quantity comes from the plan, never hand-entered --}}
            <div class="bg-slate-50 rounded-lg p-4 mb-4 text-sm">
                <p><span class="text-slate-500">Publication:</span> <span class="font-medium">{{ $selectedPlan->publication->name }}</span></p>
                <p><span class="text-slate-500">Approved quantity:</span> <span class="font-medium">{{ number_format($selectedPlan->final_quantity) }} copies</span></p>
            </div>

            <form method="POST" action="{{ route('media.print-orders.store-from-plan', $selectedPlan) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Printing Press (Vendor)</label>
                    <select name="vendor_id"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Not selected —</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Order Date *</label>
                        <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Print Date</label>
                        <input type="date" name="print_date" value="{{ old('print_date') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Special Instructions / Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                        Create Print Order
                    </button>
                    <a href="{{ route('media.print-orders.index') }}" class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                        Cancel
                    </a>
                </div>
            </form>
        @else
            {{-- AD-HOC: no plan behind it, ordered_quantity hand-entered --}}
            <form method="POST" action="{{ route('media.print-orders.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Publication *</label>
                    <select name="publication_id" required
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select publication</option>
                        @foreach($publications as $publication)
                            <option value="{{ $publication->id }}" {{ old('publication_id') == $publication->id ? 'selected' : '' }}>{{ $publication->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Printing Press (Vendor)</label>
                    <select name="vendor_id"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Not selected —</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Order Date *</label>
                        <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Print Date</label>
                        <input type="date" name="print_date" value="{{ old('print_date') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ordered Quantity *</label>
                    <input type="number" name="ordered_quantity" min="1" value="{{ old('ordered_quantity') }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Special Instructions / Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                        Create Print Order
                    </button>
                    <a href="{{ route('media.print-orders.index') }}" class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
