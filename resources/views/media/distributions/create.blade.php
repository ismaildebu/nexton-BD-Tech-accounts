@extends('layouts.app')
@section('page-title', 'New Distribution')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('media.distributions.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Publication</label>
                    <select name="publication_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select publication...</option>
                        @foreach($publications as $pub)
                            <option value="{{ $pub->id }}">{{ $pub->name }}</option>
                        @endforeach
                    </select>
                    @error('publication_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Distribution Date</label>
                    <input type="date" name="distribution_date" value="{{ date('Y-m-d') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @error('distribution_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-3">Distribution Items</label>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-slate-200 rounded-lg">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium text-slate-600">Party</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Paid Qty</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Free %</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Rate</th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2">
                                    <select name="items[0][media_party_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm" required>
                                        <option value="">Select party...</option>
                                        @foreach($parties as $party)
                                            <option value="{{ $party->id }}">{{ $party->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[0][paid_quantity]" min="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[0][free_percentage]" min="0" max="100" step="0.01" value="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[0][rate]" min="0" step="0.01" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" onclick="addRow()"
                        class="mt-2 text-sm text-blue-600 hover:underline">+ Add Row</button>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Distribution
                </button>
                <a href="{{ route('media.distributions.index') }}" class="px-4 py-2 rounded-lg text-sm border border-slate-300 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<script>
let rowIndex = 1;
const parties = @json($parties);
function addRow() {
    const tbody = document.getElementById('items-body');
    const options = parties.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    tbody.insertAdjacentHTML('beforeend', `
        <tr>
            <td class="px-3 py-2"><select name="items[${rowIndex}][media_party_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm" required><option value="">Select party...</option>${options}</select></td>
            <td class="px-3 py-2"><input type="number" name="items[${rowIndex}][paid_quantity]" min="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required></td>
            <td class="px-3 py-2"><input type="number" name="items[${rowIndex}][free_percentage]" min="0" max="100" step="0.01" value="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right"></td>
            <td class="px-3 py-2"><input type="number" name="items[${rowIndex}][rate]" min="0" step="0.01" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required></td>
        </tr>`);
    rowIndex++;
}
</script>
@endsection
