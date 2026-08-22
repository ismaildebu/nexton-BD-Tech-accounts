@extends('layouts.app')

@section('title','Company Details')

@section('page-title','Company Details')

@section('page-subtitle', $company->company_name)

@section('content')

    <div class="py-6">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-2xl font-bold">
                        {{ $company->company_name }}
                    </h3>

                    <a href="{{ route('companies.edit', $company->id) }}"
                       class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">
                        Edit
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Business Type</p>
                        <p class="font-medium">{{ $company->business_type ?? '-' }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Created</p>
                        <p class="font-medium">{{ $company->created_at?->format('d M Y') ?? '-' }}</p>
                    </div>
                </div>

                <a href="{{ route('companies.index') }}"
                   class="inline-block mt-6 text-sm text-blue-600 hover:underline">
                    ← Back to Companies
                </a>

            </div>

        </div>

    </div>

@endsection