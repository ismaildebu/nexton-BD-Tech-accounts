@extends('layouts.app')

@section('content')

<div class="container mx-auto p-6">

    <div class="mb-6">

        <h1 class="text-2xl font-bold">
            Add Expense
        </h1>

        <p class="text-gray-500">
            Create a new company expense
        </p>

    </div>


    @if ($errors->any())

        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">

            <ul>

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif



    <div class="bg-white shadow rounded p-6">


        <form method="POST" action="{{ route('expenses.store') }}">

            @csrf


            <div class="mb-4">

                <label class="block font-medium mb-2">
                    Expense Date
                </label>

                <input
                    type="date"
                    name="expense_date"
                    value="{{ old('expense_date', date('Y-m-d')) }}"
                    class="border rounded w-full p-2"
                    required
                >

            </div>



            <div class="mb-4">

                <label class="block font-medium mb-2">
                    Category
                </label>

                <input
                    type="text"
                    name="category"
                    value="{{ old('category') }}"
                    placeholder="Example: Office Rent"
                    class="border rounded w-full p-2"
                    required
                >

            </div>



            <div class="mb-4">

                <label class="block font-medium mb-2">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="3"
                    class="border rounded w-full p-2"
                    placeholder="Expense details"
                >{{ old('description') }}</textarea>

            </div>



            <div class="mb-4">

                <label class="block font-medium mb-2">
                    Amount
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="amount"
                    value="{{ old('amount') }}"
                    placeholder="0.00"
                    class="border rounded w-full p-2"
                    required
                >

            </div>



            <div class="flex gap-3">


                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded">

                    Save Expense

                </button>



                <a href="{{ route('expenses.index') }}"
                   class="bg-gray-500 text-white px-5 py-2 rounded">

                    Cancel

                </a>


            </div>


        </form>


    </div>


</div>

@endsection