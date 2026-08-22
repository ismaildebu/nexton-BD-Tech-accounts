@extends('layouts.app')

@section('page-title', 'New Print Plan')
@section('page-subtitle', 'The recommended quantity is calculated automatically from distribution history')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-6">Plan Details</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('media.print-plans.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Publication *</label>
                <select name="publication_id" required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select publication</option>
                    @foreach($publications as $publication)
                        <option value="{{ $publication->id }}" {{ old('publication_id') == $publication->id ? 'selected' : '' }}>
                            {{ $publication->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Plan Date *</label>
                <input type="date" name="plan_date" value="{{ old('plan_date', now()->addDay()->toDateString()) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <p class="text-xs text-slate-500">
                previous distribution, average distribution, expected paid/free quantity and buffer are all
                calculated automatically once you save — you don't need to enter them here.
            </p>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Calculate & Save as Draft
                </button>
                <a href="{{ route('media.print-plans.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
