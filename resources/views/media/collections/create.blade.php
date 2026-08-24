@extends('layouts.app')
@section('page-title', 'New Collection')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('media.collections.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Party</label>
                <select name="media_party_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select party...</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" {{ old('media_party_id') == $party->id ? 'selected' : '' }}>
                            {{ $party->name }} ({{ $party->type }})
                        </option>
                    @endforeach
                </select>
                @error('media_party_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Receiving Account (Cash / Bank)</label>
                <select name="account_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select account...</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                @error('account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Collection Date</label>
                    <input type="date" name="collection_date" value="{{ old('collection_date', date('Y-m-d')) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @error('collection_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           value="{{ old('amount') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- payment_method was missing from the original Phase 1 view,
                 causing every submission to fail validation silently --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    @foreach([
                        \App\Models\MediaCollection::METHOD_CASH,
                        \App\Models\MediaCollection::METHOD_BANK,
                        \App\Models\MediaCollection::METHOD_MOBILE_BANKING,
                        \App\Models\MediaCollection::METHOD_CHEQUE,
                        \App\Models\MediaCollection::METHOD_OTHER,
                    ] as $method)
                        <option value="{{ $method }}" {{ old('payment_method', 'Cash') === $method ? 'selected' : '' }}>
                            {{ $method }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Reference <span class="text-slate-400 font-normal">(cheque no., transaction ID, etc.)</span>
                </label>
                <input type="text" name="reference" value="{{ old('reference') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" maxlength="150">
                @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Collection
                </button>
                <a href="{{ route('media.collections.index') }}" class="px-4 py-2 rounded-lg text-sm border border-slate-300 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
