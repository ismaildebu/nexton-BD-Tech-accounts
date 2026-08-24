@extends('layouts.app')
@section('page-title', 'New Newspaper Return')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('media.returns.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Publication</label>
                    <select name="publication_id" id="publication_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required onchange="loadDistributions()">
                        <option value="">Select publication...</option>
                        @foreach($publications as $pub)
                            <option value="{{ $pub->id }}" {{ old('publication_id') == $pub->id ? 'selected' : '' }}>
                                {{ $pub->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('publication_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Return Date</label>
                    <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @error('return_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Link to Distribution <span class="text-slate-400 font-normal">(optional — if linked, return quantities are validated against distributed quantities)</span>
                    </label>
                    <select name="media_distribution_id" id="distribution_select" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" onchange="loadDistributionParties()">
                        <option value="">— No distribution link —</option>
                        @foreach($distributions as $dist)
                            <option value="{{ $dist->id }}"
                                data-pub="{{ $dist->publication_id }}"
                                {{ old('media_distribution_id') == $dist->id ? 'selected' : '' }}>
                                #{{ $dist->id }} — {{ $dist->publication?->name }} — {{ $dist->distribution_date?->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                    @error('media_distribution_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @error('items')
                <p class="text-red-500 text-sm bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $message }}</p>
            @enderror

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-3">Return Items</label>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-slate-200 rounded-lg">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium text-slate-600">Party</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Paid Return</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Free Return</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Total</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                        </tbody>
                    </table>
                </div>
                <button type="button" onclick="addRow()"
                        class="mt-2 text-sm text-blue-600 hover:underline">+ Add Row</button>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Return
                </button>
                <a href="{{ route('media.returns.index') }}" class="px-4 py-2 rounded-lg text-sm border border-slate-300 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let rowIndex = 0;
const allParties = @json($distributions->flatMap(fn($d) => $d->items ?? collect())->pluck('party')->filter()->unique('id')->values());

// All parties across all distributions for manual entry
const allDistributions = @json($distributions->map(fn($d) => [
    'id' => $d->id,
    'publication_id' => $d->publication_id,
    'items' => $d->items ? $d->items->map(fn($i) => [
        'media_party_id' => $i->media_party_id,
        'party_name' => optional($i->party)->name,
        'net_quantity' => $i->net_quantity,
        'paid_quantity' => $i->paid_quantity,
        'free_quantity' => $i->free_quantity,
    ]) : []
]));

const allPartiesFlat = @json(\App\Models\MediaParty::where('company_id', session('company_id'))->active()->get(['id','name','type']));

function buildPartyOptions(parties) {
    return parties.map(p => `<option value="${p.id}">${p.name} (${p.type})</option>`).join('');
}

function addRow(partyId = '', partyName = '', netQty = null) {
    const tbody = document.getElementById('items-body');
    const options = buildPartyOptions(allPartiesFlat);
    const netHint = netQty !== null
        ? `<span class="text-slate-400 text-xs">(max: ${netQty})</span>`
        : '';

    tbody.insertAdjacentHTML('beforeend', `
        <tr id="row-${rowIndex}">
            <td class="px-3 py-2">
                <select name="items[${rowIndex}][media_party_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm" required onchange="recalcRow(${rowIndex})">
                    <option value="">Select party...</option>
                    ${options}
                </select>
                ${netHint}
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${rowIndex}][paid_return_quantity]" min="0" value="0"
                       class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right"
                       oninput="recalcRow(${rowIndex})" required>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${rowIndex}][free_return_quantity]" min="0" value="0"
                       class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right"
                       oninput="recalcRow(${rowIndex})" required>
            </td>
            <td class="px-3 py-2 text-right font-medium total-cell" id="total-${rowIndex}">0</td>
            <td class="px-3 py-2">
                <button type="button" onclick="document.getElementById('row-${rowIndex}').remove()" class="text-red-400 hover:text-red-600 text-xs">✕</button>
            </td>
        </tr>`);

    if (partyId) {
        const sel = tbody.querySelector(`#row-${rowIndex} select`);
        sel.value = partyId;
    }

    rowIndex++;
}

function recalcRow(idx) {
    const paid = parseInt(document.querySelector(`[name="items[${idx}][paid_return_quantity]"]`)?.value) || 0;
    const free = parseInt(document.querySelector(`[name="items[${idx}][free_return_quantity]"]`)?.value) || 0;
    const cell = document.getElementById(`total-${idx}`);
    if (cell) cell.textContent = paid + free;
}

function loadDistributionParties() {
    const distId = parseInt(document.getElementById('distribution_select').value);
    const tbody = document.getElementById('items-body');
    tbody.innerHTML = '';
    rowIndex = 0;

    if (!distId) {
        addRow();
        return;
    }

    const dist = allDistributions.find(d => d.id === distId);
    if (!dist || !dist.items.length) {
        addRow();
        return;
    }

    dist.items.forEach(item => {
        if (item.net_quantity > 0) {
            addRow(item.media_party_id, item.party_name, item.net_quantity);
        }
    });

    if (!tbody.children.length) addRow();
}

function loadDistributions() {
    // When publication changes, reset distribution dropdown filter (visual only)
    loadDistributionParties();
}

// Init
addRow();
</script>
@endsection
