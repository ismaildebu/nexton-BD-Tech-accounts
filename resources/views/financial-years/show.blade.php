@extends('layouts.app')

@section('title','Financial Year Details')

@section('page-title','Financial Year Details')

@section('page-subtitle','View company financial year information')

@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Financial Year Details
    </h2>

</div>

@endsection

@section('content')
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <div class="mb-4">
                    <strong>Company:</strong><br>
                    {{ $financialYear->company->company_name ?? '' }}
                </div>

                <div class="mb-4">
                    <strong>Financial Year:</strong><br>
                    {{ $financialYear->year_name }}
                </div>

                <div class="mb-4">
                    <strong>Start Date:</strong><br>
                    {{ $financialYear->start_date }}
                </div>

                <div class="mb-4">
                    <strong>End Date:</strong><br>
                    {{ $financialYear->end_date }}
                </div>

                <div class="mb-4">
                    <strong>Active:</strong><br>
                    {{ $financialYear->is_active ? 'Yes' : 'No' }}
                </div>

                <div class="mb-4">
                    <strong>Closed:</strong><br>
                    {{ $financialYear->is_closed ? 'Yes' : 'No' }}
                </div>

                <a href="{{ route('financial-years.index') }}"
                   class="bg-gray-600 text-white px-4 py-2 rounded">
                    Back
                </a>

            </div>

        </div>
    </div>

@endsection