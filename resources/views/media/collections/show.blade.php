@extends('layouts.app')
@section('page-title', 'Collection Detail')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-slate-500">Party:</span>
                <span class="font-medium ml-1">{{ $collection->party->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500">Date:</span>
                <span class="font-medium ml-1">{{ $collection->collection_date }}</span>
            </div>
            <div>
                <span class="text-slate-500">Account:</span>
                <span class="font-medium ml-1">{{ $collection->account->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500">Amount:</span>
                <span class="font-medium ml-1">{{ number_format($collection->amount, 2) }}</span>
            </div>
            @if($collection->notes)
            <div class="col-span-2">
                <span class="text-slate-500">Notes:</span>
                <span class="ml-1">{{ $collection->notes }}</span>
            </div>
            @endif
        </div>
        <div class="pt-2 border-t">
            <a href="{{ route('media.collections.index') }}" class="text-blue-600 hover:underline text-sm">
                ← Back to Collections
            </a>
        </div>
    </div>
</div>
@endsection
