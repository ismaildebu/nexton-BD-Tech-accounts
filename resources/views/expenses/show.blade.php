@extends('layouts.app')

@section('title','Expense Details')

@section('page-title','Expense Details')

@section('page-subtitle','View a company expense')


@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Expense Details
    </h2>

</div>

@endsection


@section('content')
<div class="container mx-auto p-6">

    <div class="bg-white shadow rounded p-6 max-w-3xl">

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <dt class="text-sm text-gray-500">Expense Date</dt>
                <dd class="font-medium">{{ $expense->expense_date?->format('Y-m-d') }}</dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Category</dt>
                <dd class="font-medium">{{ $expense->category }}</dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Amount</dt>
                <dd class="font-medium">{{ number_format((float) $expense->amount, 2) }}</dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Status</dt>
                <dd class="font-medium">{{ ucfirst((string) $expense->status) }}</dd>
            </div>

            <div class="md:col-span-2">
                <dt class="text-sm text-gray-500">Description</dt>
                <dd class="font-medium whitespace-pre-line">{{ $expense->description ?: '-' }}</dd>
            </div>

        </dl>

        <div class="flex gap-3 mt-8">

            @can('expenses.edit')
                <a href="{{ route('expenses.edit', $expense) }}"
                   class="bg-yellow-500 text-white px-5 py-2 rounded hover:bg-yellow-600">
                    Edit
                </a>
            @endcan

            <a href="{{ route('expenses.index') }}"
               class="bg-gray-500 text-white px-5 py-2 rounded">
                Back
            </a>

        </div>

    </div>

</div>

@endsection
