@extends('layouts.app')

@section('page-title', 'New Print Order')

@section('page-subtitle', 'Create print order from actual distribution demand')

@section('content')

<div class="max-w-2xl mx-auto space-y-4">

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">

        <h2 class="text-lg font-semibold mb-6">
            Demand-Based Print Order
        </h2>

        <form method="POST"
              action="{{ route('media.print-orders.store') }}"
              class="space-y-4">

            @csrf

            {{-- Publication --}}
            <div>
                <label
                    for="publication_id"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Publication *
                </label>

                <select
                    id="publication_id"
                    name="publication_id"
                    required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">
                        Select publication
                    </option>

                    @foreach($publications as $publication)
                        @php
                            $distribution = $latestDistributions->get($publication->id);
                        @endphp

                        <option
                            value="{{ $publication->id }}"
                            data-demand="{{ $distribution?->total_quantity ?? 0 }}"
                            data-distribution-date="{{ $distribution?->distribution_date?->format('d M Y') ?? '' }}"
                            @selected(old('publication_id') == $publication->id)
                        >
                            {{ $publication->name }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-slate-500 mt-1">
                    Demand is taken automatically from the latest confirmed distribution.
                </p>
            </div>

            {{-- Latest Distribution --}}
            <div
                id="distribution-info"
                class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm"
            >
                <div class="space-y-1">

                    <p>
                        <span class="text-slate-500">
                            Latest Confirmed Distribution:
                        </span>

                        <span
                            id="distribution-date"
                            class="font-medium text-slate-800"
                        ></span>
                    </p>

                    <p>
                        <span class="text-slate-500">
                            Actual Demand:
                        </span>

                        <span
                            id="distribution-demand"
                            class="font-semibold text-slate-800"
                        >0</span>
                    </p>

                </div>
            </div>

            {{-- Demand Quantity --}}
            <div>
                <label
                    for="demand_quantity"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Demand Quantity
                </label>

                <input
                    type="number"
                    id="demand_quantity"
                    readonly
                    tabindex="-1"
                    class="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2 text-sm cursor-not-allowed"
                >

                <p class="text-xs text-slate-500 mt-1">
                    Automatically calculated from the latest confirmed distribution.
                </p>
            </div>

            {{-- Buffer Percentage --}}
            <div>
                <label
                    for="buffer_percentage"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Buffer Percentage (%) *
                </label>

                <input
                    type="number"
                    id="buffer_percentage"
                    name="buffer_percentage"
                    min="0"
                    max="100"
                    step="0.01"
                    value="{{ old('buffer_percentage', 0) }}"
                    required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <p class="text-xs text-slate-500 mt-1">
                    Extra copies above actual demand.
                </p>
            </div>

            {{-- Buffer Quantity --}}
            <div>
                <label
                    for="buffer_quantity"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Buffer Quantity
                </label>

                <input
                    type="number"
                    id="buffer_quantity"
                    readonly
                    tabindex="-1"
                    class="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2 text-sm cursor-not-allowed"
                >

                <p class="text-xs text-slate-500 mt-1">
                    Automatically calculated using the buffer percentage.
                </p>
            </div>

            {{-- Final Quantity --}}
            <div>
                <label
                    for="final_quantity"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Final Print Quantity
                </label>

                <input
                    type="number"
                    id="final_quantity"
                    readonly
                    tabindex="-1"
                    class="w-full bg-green-50 border border-green-300 rounded-lg px-3 py-2 text-sm font-semibold cursor-not-allowed"
                >

                <p class="text-xs text-slate-500 mt-1">
                    Demand Quantity + Buffer Quantity.
                </p>
            </div>

            {{-- Vendor --}}
            <div>
                <label
                    for="vendor_id"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Printing Press (Vendor)
                </label>

                <select
                    id="vendor_id"
                    name="vendor_id"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">
                        — Not selected —
                    </option>

                    @foreach($vendors as $vendor)
                        <option
                            value="{{ $vendor->id }}"
                            @selected(old('vendor_id') == $vendor->id)
                        >
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label
                        for="order_date"
                        class="block text-sm font-medium text-slate-700 mb-1"
                    >
                        Order Date *
                    </label>

                    <input
                        type="date"
                        id="order_date"
                        name="order_date"
                        value="{{ old('order_date', now()->toDateString()) }}"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label
                        for="print_date"
                        class="block text-sm font-medium text-slate-700 mb-1"
                    >
                        Print Date
                    </label>

                    <input
                        type="date"
                        id="print_date"
                        name="print_date"
                        value="{{ old('print_date') }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

            </div>

            {{-- Cost --}}
            <div>
                <label
                    for="unit_printing_cost"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Unit Printing Cost (৳) *
                </label>

                <input
                    type="number"
                    id="unit_printing_cost"
                    name="unit_printing_cost"
                    min="0.0001"
                    step="0.0001"
                    value="{{ old('unit_printing_cost') }}"
                    required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <p class="text-xs text-slate-500 mt-1">
                    Printing cost per copy.
                </p>
            </div>

            {{-- Notes --}}
            <div>
                <label
                    for="notes"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Special Instructions / Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="2"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Create Print Order
                </button>

                <a
                    href="{{ route('media.print-orders.index') }}"
                    class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const publication = document.getElementById('publication_id');
    const demand = document.getElementById('demand_quantity');
    const bufferPercentage = document.getElementById('buffer_percentage');
    const bufferQuantity = document.getElementById('buffer_quantity');
    const finalQuantity = document.getElementById('final_quantity');

    const distributionInfo = document.getElementById('distribution-info');
    const distributionDate = document.getElementById('distribution-date');
    const distributionDemand = document.getElementById('distribution-demand');

    function calculate() {
        const selected = publication.options[publication.selectedIndex];

        if (!selected || !selected.value) {
            demand.value = 0;
            bufferQuantity.value = 0;
            finalQuantity.value = 0;

            distributionInfo.classList.add('hidden');
            distributionDate.textContent = '';
            distributionDemand.textContent = '0';

            return;
        }

        const demandValue = Number(
            selected.dataset.demand || 0
        );

        const percentage = Math.min(
            100,
            Math.max(
                0,
                Number(bufferPercentage.value || 0)
            )
        );

        const bufferValue = Math.ceil(
            demandValue * (percentage / 100)
        );

        const finalValue = demandValue + bufferValue;

        demand.value = demandValue;
        bufferQuantity.value = bufferValue;
        finalQuantity.value = finalValue;

        const date = selected.dataset.distributionDate || '';

        if (demandValue > 0) {
            distributionInfo.classList.remove('hidden');

            distributionDate.textContent =
                date || 'Date unavailable';

            distributionDemand.textContent =
                demandValue.toLocaleString();
        } else {
            distributionInfo.classList.add('hidden');

            distributionDate.textContent = '';
            distributionDemand.textContent = '0';
        }
    }

    publication.addEventListener(
        'change',
        calculate
    );

    bufferPercentage.addEventListener(
        'input',
        calculate
    );

    calculate();
});
</script>

@endsection