@extends('layouts.app')

@section('page-title', 'New Distribution')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-slate-800">
                New Distribution
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                The latest distribution list will be loaded as a template.
                Saving creates a new distribution and does not change the previous one.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('media.distributions.store') }}"
            method="POST"
            class="space-y-6"
        >
            @csrf

            {{-- Publication + Date --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label
                        for="publication_id"
                        class="block text-sm font-medium text-slate-700 mb-1"
                    >
                        Publication
                    </label>

                    <select
                        name="publication_id"
                        id="publication_id"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        required
                        onchange="loadLatestDistribution()"
                    >
                        <option value="">Select publication...</option>

                        @foreach($publications as $pub)
                            <option
                                value="{{ $pub->id }}"
                                {{ old('publication_id') == $pub->id ? 'selected' : '' }}
                            >
                                {{ $pub->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('publication_id')
                        <p class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="distribution_date"
                        class="block text-sm font-medium text-slate-700 mb-1"
                    >
                        Distribution Date
                    </label>

                    <input
                        type="date"
                        name="distribution_date"
                        id="distribution_date"
                        value="{{ old('distribution_date', date('Y-m-d')) }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        required
                    >

                    @error('distribution_date')
                        <p class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Template information --}}
            <div
                id="template-info"
                class="hidden bg-blue-50 border border-blue-200 rounded-lg px-4 py-3"
            >
                <div class="flex items-start gap-3">

                    <div class="text-blue-600 text-lg">
                        ℹ
                    </div>

                    <div>
                        <p class="text-sm font-medium text-blue-800">
                            Latest distribution loaded
                        </p>

                        <p
                            id="template-info-text"
                            class="text-xs text-blue-700 mt-1"
                        ></p>

                        <p class="text-xs text-blue-700 mt-1">
                            You can keep the same quantities and save,
                            or change quantities, add parties, or remove rows.
                            The previous distribution will remain unchanged.
                        </p>
                    </div>

                </div>
            </div>

            {{-- Error --}}
            @error('items')
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    {{ $message }}
                </div>
            @enderror

            {{-- Distribution Items --}}
            <div>

                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Distribution Items
                        </label>

                        <p class="text-xs text-slate-500 mt-1">
                            Free % is calculated automatically when the distribution is saved.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-slate-200 rounded-lg">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium text-slate-600">
                                    Party
                                </th>

                                <th class="text-right px-3 py-2 font-medium text-slate-600">
                                    Paid Qty
                                </th>

                                <th class="text-right px-3 py-2 font-medium text-slate-600">
                                    Free Qty
                                </th>

                                <th class="text-right px-3 py-2 font-medium text-slate-600">
                                    Free % (auto)
                                </th>

                                <th class="text-right px-3 py-2 font-medium text-slate-600">
                                    Rate
                                </th>

                                <th class="text-center px-3 py-2 font-medium text-slate-600">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            id="items-body"
                            class="divide-y divide-slate-100"
                        >
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button
                        type="button"
                        onclick="addRow()"
                        class="text-sm text-blue-600 hover:text-blue-800 hover:underline"
                    >
                        + Add Party
                    </button>
                </div>

                <div class="mt-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">

                        <div>
                            <span class="text-slate-500">
                                Total Paid:
                            </span>

                            <strong
                                id="total-paid"
                                class="text-slate-800 ml-1"
                            >
                                0
                            </strong>
                        </div>

                        <div>
                            <span class="text-slate-500">
                                Total Free:
                            </span>

                            <strong
                                id="total-free"
                                class="text-slate-800 ml-1"
                            >
                                0
                            </strong>
                        </div>

                        <div>
                            <span class="text-slate-500">
                                Total Copies:
                            </span>

                            <strong
                                id="total-copies"
                                class="text-slate-800 ml-1"
                            >
                                0
                            </strong>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label
                    for="notes"
                    class="block text-sm font-medium text-slate-700 mb-1"
                >
                    Notes
                </label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="Optional notes..."
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="text-red-500 text-xs mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Save Distribution
                </button>

                <a
                    href="{{ route('media.distributions.index') }}"
                    class="px-5 py-2 rounded-lg text-sm border border-slate-300 hover:bg-slate-50"
                >
                    Cancel
                </a>

            </div>

        </form>
    </div>
</div>

<script>
let rowIndex = 0;

const parties = @json($parties);
const publications = @json($publications);
const latestByPublication = @json($latestDistributionData);

const systemDefaultFreePercentage =
    @json((float) config('media.default_free_percentage', 0));


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


/*
|--------------------------------------------------------------------------
| Build Party Options
|--------------------------------------------------------------------------
*/
function buildPartyOptions(selectedPartyId = '') {
    let options = `
        <option value="">Select party...</option>
    `;

    parties.forEach(function (party) {
        const selected =
            String(party.id) === String(selectedPartyId)
                ? 'selected'
                : '';

        options += `
            <option value="${party.id}" ${selected}>
                ${escapeHtml(party.name)} (${escapeHtml(party.type)})
            </option>
        `;
    });

    return options;
}


/*
|--------------------------------------------------------------------------
| Calculate Free Quantity
|--------------------------------------------------------------------------
|
| This is display-only.
|
| The server remains the source of truth for the actual free quantity.
|--------------------------------------------------------------------------
*/
function calculateFreeQuantity(paidQuantity, percentage) {
    const paid = Number(paidQuantity) || 0;
    const pct = Number(percentage) || 0;

    return Math.floor((paid * pct) / 100);
}


/*
|--------------------------------------------------------------------------
| Resolve Free Percentage For Display
|--------------------------------------------------------------------------
|
| Same priority as the backend:
|
| Party override
|      ↓
| Publication default
|      ↓
| System default
|
|--------------------------------------------------------------------------
*/
function resolveFreePercentage(partyId, publicationId) {

    const party = parties.find(function (item) {
        return String(item.id) === String(partyId);
    });

    const publication = publications.find(function (item) {
        return String(item.id) === String(publicationId);
    });

    if (
        party &&
        party.free_percentage !== null &&
        party.free_percentage !== undefined
    ) {
        return {
            percentage: Number(party.free_percentage),
            source: 'party override'
        };
    }

    if (
        publication &&
        publication.default_free_percentage !== null &&
        publication.default_free_percentage !== undefined
    ) {
        return {
            percentage: Number(publication.default_free_percentage),
            source: 'publication default'
        };
    }

    return {
        percentage: Number(systemDefaultFreePercentage),
        source: 'system default'
    };
}


/*
|--------------------------------------------------------------------------
| Refresh One Row
|--------------------------------------------------------------------------
*/
function refreshRow(row) {

    const publicationId =
        document.getElementById('publication_id').value;

    const partySelect =
        row.querySelector('.party-select');

    const paidInput =
        row.querySelector('.paid-quantity');

    const freeHint =
        row.querySelector('.free-hint');

    const freeQuantity =
        row.querySelector('.free-quantity');

    if (!partySelect || !paidInput) {
        return;
    }

    const partyId = partySelect.value;

    if (!partyId) {
        freeHint.value = '—';
        freeQuantity.value = '0';
        updateTotals();
        return;
    }

    const resolved =
        resolveFreePercentage(
            partyId,
            publicationId
        );

    const paidQuantity =
        Number(paidInput.value) || 0;

    const calculatedFree =
        calculateFreeQuantity(
            paidQuantity,
            resolved.percentage
        );

    freeHint.value =
        `${resolved.percentage.toFixed(2)}% (${resolved.source})`;

    freeQuantity.value =
        calculatedFree;

    updateTotals();
}


/*
|--------------------------------------------------------------------------
| Refresh All Rows
|--------------------------------------------------------------------------
*/
function refreshAllRows() {

    document
        .querySelectorAll('#items-body tr')
        .forEach(function (row) {
            refreshRow(row);
        });

    updateTotals();
}


/*
|--------------------------------------------------------------------------
| Add New Row
|--------------------------------------------------------------------------
*/
function addRow(data = null) {

    const tbody =
        document.getElementById('items-body');

    const index =
        rowIndex++;

    const selectedPartyId =
        data ? data.media_party_id : '';

    const paidQuantity =
        data ? data.paid_quantity : '';

    const rate =
        data ? data.rate : '';

    const row = document.createElement('tr');

    row.innerHTML = `
        <td class="px-3 py-2">

            <select
                name="items[${index}][media_party_id]"
                class="party-select w-full border border-slate-300 rounded px-2 py-1 text-sm"
                required
            >
                ${buildPartyOptions(selectedPartyId)}
            </select>

        </td>

        <td class="px-3 py-2">

            <input
                type="number"
                name="items[${index}][paid_quantity]"
                value="${escapeHtml(paidQuantity)}"
                min="0"
                class="paid-quantity w-full border border-slate-300 rounded px-2 py-1 text-sm text-right"
                required
            >

        </td>

        <td class="px-3 py-2">

            <input
                type="number"
                class="free-quantity w-full border border-slate-200 bg-slate-50 rounded px-2 py-1 text-sm text-right text-slate-500"
                value="0"
                readonly
                tabindex="-1"
            >

        </td>

        <td class="px-3 py-2">

            <input
                type="text"
                class="free-hint w-full border border-slate-200 bg-slate-50 rounded px-2 py-1 text-sm text-right text-slate-500"
                value="—"
                readonly
                tabindex="-1"
            >

        </td>

        <td class="px-3 py-2">

            <input
                type="number"
                name="items[${index}][rate]"
                value="${escapeHtml(rate)}"
                min="0"
                step="0.01"
                class="rate-input w-full border border-slate-300 rounded px-2 py-1 text-sm text-right"
                required
            >

        </td>

        <td class="px-3 py-2 text-center">

            <button
                type="button"
                onclick="removeRow(this)"
                class="text-red-600 hover:text-red-800 text-xs hover:underline"
            >
                Remove
            </button>

        </td>
    `;

    
    tbody.appendChild(row);

const partySelect =
    row.querySelector('.party-select');

const paidInput =
    row.querySelector('.paid-quantity');

/*
 * Explicitly restore the selected party after the row
 * has been inserted into the DOM.
 */
if (selectedPartyId !== null && selectedPartyId !== undefined && selectedPartyId !== '') {
    partySelect.value = String(selectedPartyId);
}


    partySelect.addEventListener(
        'change',
        function () {
            refreshRow(row);
        }
    );

    paidInput.addEventListener(
        'input',
        function () {
            refreshRow(row);
        }
    );

    refreshRow(row);
}


/*
|--------------------------------------------------------------------------
| Remove Row
|--------------------------------------------------------------------------
*/
function removeRow(button) {

    const row =
        button.closest('tr');

    if (!row) {
        return;
    }

    row.remove();

    updateTotals();
}


/*
|--------------------------------------------------------------------------
| Update Totals
|--------------------------------------------------------------------------
*/
function updateTotals() {

    let totalPaid = 0;
    let totalFree = 0;

    document
        .querySelectorAll('#items-body tr')
        .forEach(function (row) {

            const paidInput =
                row.querySelector('.paid-quantity');

            const freeInput =
                row.querySelector('.free-quantity');

            totalPaid +=
                Number(paidInput?.value) || 0;

            totalFree +=
                Number(freeInput?.value) || 0;
        });

    const totalCopies =
        totalPaid + totalFree;

    document.getElementById('total-paid').textContent =
        totalPaid.toLocaleString();

    document.getElementById('total-free').textContent =
        totalFree.toLocaleString();

    document.getElementById('total-copies').textContent =
        totalCopies.toLocaleString();
}


/*
|--------------------------------------------------------------------------
| Load Latest Distribution
|--------------------------------------------------------------------------
*/
function loadLatestDistribution() {

    const publicationId =
        document.getElementById('publication_id').value;

    const tbody =
        document.getElementById('items-body');

    const templateInfo =
        document.getElementById('template-info');

    const templateInfoText =
        document.getElementById('template-info-text');

    /*
     * Clear current rows.
     */
    tbody.innerHTML = '';

    rowIndex = 0;

    /*
     * Nothing selected.
     */
    if (!publicationId) {

        templateInfo.classList.add('hidden');

        addRow();

        return;
    }

    const latest =
        latestByPublication[publicationId];

    /*
     * No previous distribution exists for this publication.
     */
    if (
        !latest ||
        !Array.isArray(latest.items) ||
        latest.items.length === 0
    ) {

        templateInfo.classList.add('hidden');

        addRow();

        return;
    }

    /*
     * Show template information.
     */
    templateInfo.classList.remove('hidden');

    templateInfoText.textContent =
        `Template loaded from distribution date: ${latest.distribution_date || 'previous distribution'}.`;

    /*
     * Load previous rows as editable NEW rows.
     *
     * Important:
     * These are only copied values.
     * The previous distribution is never edited.
     */
    latest.items.forEach(function (item) {
        addRow({
            media_party_id: item.media_party_id,
            paid_quantity: item.paid_quantity,
            rate: item.rate
        });
    });

    updateTotals();
}


/*
|--------------------------------------------------------------------------
| Initial Page Load
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function () {

    const publicationId =
        document.getElementById('publication_id').value;

    if (publicationId) {
        loadLatestDistribution();
    } else {
        addRow();
    }

    updateTotals();
});
</script>
@endsection