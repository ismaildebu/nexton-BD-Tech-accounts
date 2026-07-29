@extends('layouts.app')

@section('content')

<div class="container mx-auto p-6">

    <div class="flex justify-between items-center mb-6">

        <div>
            <h1 class="text-2xl font-bold">
                Expenses
            </h1>

            <p class="text-gray-500">
                Manage company expenses
            </p>
        </div>


        <a href="{{ route('expenses.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded">

            + Add Expense

        </a>

    </div>



    @if(session('success'))

        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">

            {{ session('success') }}

        </div>

    @endif



    <div class="bg-white shadow rounded">

        <table class="w-full">

            <thead class="border-b">

                <tr>

                    <th class="p-3 text-left">
                        Date
                    </th>

                    <th class="p-3 text-left">
                        Category
                    </th>

                    <th class="p-3 text-left">
                        Description
                    </th>

                    <th class="p-3 text-left">
                        Amount
                    </th>

                    <th class="p-3">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


            @forelse($expenses as $expense)

                <tr class="border-b">


                    <td class="p-3">
                        {{ $expense->expense_date?->format('d M, Y') }}
                    </td>


                    <td class="p-3">
                        {{ $expense->category }}
                    </td>


                    <td class="p-3">
                        {{ $expense->description ?? '-' }}
                    </td>


                    <td class="p-3">
                        ৳{{ number_format($expense->amount,2) }}
                    </td>


                    <td class="p-3">

                        <a href="{{ route('expenses.edit',$expense->id) }}"
                           class="text-blue-600">
                            Edit
                        </a>


                        <form action="{{ route('expenses.destroy',$expense->id) }}"
                              method="POST"
                              class="inline">

                            @csrf
                            @method('DELETE')


                            <button
                                class="text-red-600 ml-3"
                                onclick="return confirm('Delete expense?')">

                                Delete

                            </button>

                        </form>

                    </td>


                </tr>


            @empty

                <tr>

                    <td colspan="5"
                        class="p-5 text-center text-gray-500">

                        No expenses found.

                    </td>

                </tr>


            @endforelse


            </tbody>


        </table>


    </div>


</div>

@endsection