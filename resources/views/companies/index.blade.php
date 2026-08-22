
@extends('layouts.app')

@section('title','Companies')

@section('page-title','Companies')

@section('page-subtitle','Manage your companies')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Company List
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold">
                        Companies
                    </h3>

                    @if(auth()->user()->can('companies.create'))
                        <a href="{{ route('companies.create') }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            + Add Company
                        </a>
                    @endif
                </div>


               @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif


                <table class="w-full border border-gray-300">

                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2 text-left">
                                ID
                            </th>

                            <th class="border p-2 text-left">
                                Company Name
                            </th>

                            <th class="border p-2 text-left">
                                Actions
                            </th>
                        </tr>
                    </thead>


                    <tbody>

                        @forelse($companies as $company)

                            <tr>

                                <td class="border p-2">
                                    {{ $company->id }}
                                </td>


                                <td class="border p-2">
                                    {{ $company->company_name }}
                                </td>


                                <td class="border p-2">
                                    <form action="{{ route('switch.company') }}" method="POST" class="inline">
    @csrf

    <input type="hidden" name="company_id" value="{{ $company->id }}">

    <button type="submit"
        class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
        Select
    </button>
</form>

                                    @if(auth()->user()->can('companies.edit'))
                                        <a href="{{ route('companies.edit', $company->id) }}"
                                           class="bg-yellow-500 text-white px-3 py-1 rounded">
                                            Edit
                                        </a>
                                    @endif

                                    @if(auth()->user()->can('companies.delete'))
                                        <form action="{{ route('companies.destroy', $company->id) }}"
                                              method="POST"
                                              class="inline">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    onclick="return confirm('Are you sure you want to delete this company?')"
                                                    class="bg-red-600 text-white px-3 py-1 rounded">
                                                Delete
                                            </button>

                                        </form>
                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>
                                <td colspan="3"
                                    class="border p-4 text-center">
                                    No Company Found
                                </td>
                            </tr>

                        @endforelse


                    </tbody>

                </table>


            </div>

        </div>
    </div>

@endsection