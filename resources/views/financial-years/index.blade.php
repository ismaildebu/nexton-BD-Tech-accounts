@extends('layouts.app')

@section('title','Edit Financial Year')

@section('page-title','Edit Financial Year')

@section('page-subtitle','Update company financial year')

@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Edit Financial Year
    </h2>

</div>

@endsection


@section('content')


    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <table class="w-full border border-gray-300">

                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Company</th>
                            <th class="border p-2">Year</th>
                            <th class="border p-2">Start Date</th>
                            <th class="border p-2">End Date</th>
                            <th class="border p-2">Active</th>
                            <th class="border p-2">Closed</th>
                            <th class="border p-2 text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse($financialYears as $year)

                        <tr>
                            <td class="border p-2">
                                {{ $year->company->company_name ?? '' }}
                            </td>

                            <td class="border p-2">
                                {{ $year->year_name }}
                            </td>

                            <td class="border p-2">
                                {{ $year->start_date }}
                            </td>

                            <td class="border p-2">
                                {{ $year->end_date }}
                            </td>

                            <td class="border p-2 text-center">
                                {{ $year->is_active ? 'Yes' : 'No' }}
                            </td>

                            <td class="border p-2 text-center">
                                {{ $year->is_closed ? 'Yes' : 'No' }}
                             </td>   
                            
                            <td class="border p-2 text-center">

    <a href="{{ route('financial-years.show', $year) }}"
       class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
        View
    </a>


    <a href="{{ route('financial-years.edit', $year) }}"
       class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
        Edit
    </a>

<form action="{{ route('financial-years.destroy', $year) }}"
      method="POST"
      style="display:inline;">

    @csrf
    @method('DELETE')

    <button type="submit"
            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
            onclick="return confirm('Are you sure you want to delete this Financial Year?')">

        Delete

    </button>

</form>


                 </td>
                        </tr>

                    @empty

                        <tr>
                            
                        <td colspan="7" class="border p-4 text-center">
                                No Financial Year Found
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    </div>

@endsection