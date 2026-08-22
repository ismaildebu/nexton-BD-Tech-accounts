@extends('layouts.app')
@section('page-title', 'New Return')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('media.returns.store') }}" method="POST" class="space-y-6">
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
                    <label class="block text-sm font-medium text-slate-700 mb-1">Return Date</label>
                    <input type="date" name="return_date" value="{{ date('Y-m-d') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @error('return_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Distribution Reference</label>
                    <input type="number" name="media_distribution_id" placeholder="Distribution ID (optional)"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('media_distribution_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-3">Return Items</label>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-slate-200 rounded-lg">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium text-slate-600">Party</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Paid Return Qty</th>
                                <th class="text-right px-3 py-2 font-medium text-slate-600">Free Return Qty</th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2">
                                    <input type="text" name="items[0][party_name]" placeholder="Party name"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[0][paid_return_quantity]" min="0" value="0"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[0][free_return_quantity]" min="0" value="0"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required>
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
let rowIndex = 1;
function addRow() {
    const tbody = document.getElementById('items-body');
    tbody.insertAdjacentHTML('beforeend', `
        <tr>
            <td class="px-3 py-2"><input type="text" name="items[${rowIndex}][party_name]" placeholder="Party name" class="w-full border border-slate-300 rounded px-2 py-1 text-sm" required></td>
            <td class="px-3 py-2"><input type="number" name="items[${rowIndex}][paid_return_quantity]" min="0" value="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required></td>
            <td class="px-3 py-2"><input type="number" name="items[${rowIndex}][free_return_quantity]" min="0" value="0" class="w-full border border-slate-300 rounded px-2 py-1 text-sm text-right" required></td>
        </tr>`);
    rowIndex++;
}
</script>
@endsection
