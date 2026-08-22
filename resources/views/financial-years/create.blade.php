@extends('layouts.app')

@section('title','Add Financial Year')

@section('page-title','Add Financial Year')

@section('page-subtitle','Create company financial year')

@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Add Financial Year
    </h2>

</div>

@endsection

@section('content')
<div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('financial-years.store') }}" method="POST">

                    @csrf

<!-- Financial Year -->

<div class="mb-4">

    <label class="block font-semibold mb-2">
        Financial Year
    </label>

    <input
        type="text"
        name="year_name"
        value="{{ old('year_name') }}"
        class="w-full border rounded px-3 py-2"
        placeholder="2026-2027"
        required>

</div>

<!-- Start Date -->

<div class="mb-4">

    <label class="block font-semibold mb-2">
        Start Date
    </label>

    <input
        type="date"
        name="start_date"
        value="{{ old('start_date') }}"
        class="w-full border rounded px-3 py-2"
        required>

</div>

<!-- End Date -->

<div class="mb-4">

    <label class="block font-semibold mb-2">
        End Date
    </label>

    <input
        type="date"
        name="end_date"
        value="{{ old('end_date') }}"
        class="w-full border rounded px-3 py-2"
        required>

</div>

<!-- Active -->

<div class="mb-4">

    <label class="block font-semibold mb-2">
        Active
    </label>

    <select
        name="is_active"
        class="w-full border rounded px-3 py-2">

        <option value="1">Yes</option>
        <option value="0" selected>No</option>

    </select>

</div>

<!-- Closed -->

<div class="mb-4">

    <label class="block font-semibold mb-2">
        Closed
    </label>

    <select
        name="is_closed"
        class="w-full border rounded px-3 py-2">

        <option value="0" selected>No</option>
        <option value="1">Yes</option>

    </select>

</div>
<div class="flex gap-3">

    <button
        type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">

        Save Financial Year

    </button>

    <a href="{{ route('financial-years.index') }}"
       class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded">

        Cancel

    </a>

</div>

</form>

</div>

</div>

</div>

@endsection