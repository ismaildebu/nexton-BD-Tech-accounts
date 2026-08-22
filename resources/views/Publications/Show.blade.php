@extends('layouts.app')

@section('page-title', $publication->name)
@section('page-subtitle', 'Publication details')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $publication->name }}</h2>
                <p class="text-sm text-slate-500">{{ $publication->code }} &middot; {{ $publication->publication_type ?? '—' }}</p>
            </div>
            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $publication->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                {{ $publication->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500">Selling Price</dt>
                <dd class="font-medium">{{ number_format($publication->selling_price, 2) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Default Free %</dt>
                <dd class="font-medium">
                    {{ $publication->default_free_percentage !== null ? number_format($publication->default_free_percentage, 2) . '%' : '— (system default applies)' }}
                </dd>
            </div>
        </dl>

        <div class="flex gap-3 pt-6">
            @can('update', $publication)
            <a href="{{ route('media.publications.edit', $publication) }}"
               class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                Edit
            </a>
            @endcan
            <a href="{{ route('media.publications.index') }}"
               class="text-slate-500 px-4 py-2 text-sm">
                Back to list
            </a>
        </div>
    </div>
</div>
@endsection