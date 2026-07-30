@extends('layouts.app')

@section('title','Edit Company')

@section('page-title','Edit Company')

@section('page-subtitle','Edit company')

@section('content')

    <div class="py-6">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">


                <h3 class="text-2xl font-bold mb-6">
                    Edit Company
                </h3>


                <form action="{{ route('companies.update', $company->id) }}"
                      method="POST">

                    @csrf
                    @method('PUT')


                    <div class="mb-4">

                        <label class="block font-semibold mb-2">
                            Company Name
                        </label>


                        <input type="text"
                               name="company_name"
                               value="{{ $company->company_name }}"
                               class="w-full border rounded px-3 py-2">


                        @error('company_name')
                            <p class="text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <button type="submit"
                            class="bg-blue-600 text-white px-5 py-2 rounded">
                        Update Company
                    </button>


                    <a href="{{ route('companies.index') }}"
                       class="ml-3 bg-gray-500 text-white px-5 py-2 rounded">
                        Cancel
                    </a>


                </form>


            </div>

        </div>

    </div>

@endsection