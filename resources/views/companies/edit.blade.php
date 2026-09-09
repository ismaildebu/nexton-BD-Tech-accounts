@extends('layouts.app')

@section('title','Edit Company')

@section('page-title','Edit Company')

@section('page-subtitle','Edit company')

@section('content')

    <div class="py-6">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <h3 class="text-2xl font-bold mb-6">
                    Edit Company
                </h3>

                <form action="{{ route('companies.update', $company->id) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    {{-- Company Name --}}
                    <div class="mb-6">

                        <label class="block font-semibold mb-2">
                            Company Name
                        </label>

                        <input type="text"
                               name="company_name"
                               value="{{ old('company_name', $company->company_name) }}"
                               class="w-full border rounded px-3 py-2">

                        @error('company_name')
                            <p class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    {{-- Business Type --}}
                    <input type="hidden"
                           name="business_type"
                           value="{{ old('business_type', $company->business_type) }}">

                    {{-- Account Templates --}}
                    <div class="mb-6">

                        <h4 class="text-lg font-semibold mb-3">
                            Account Selection
                        </h4>

                        <p class="text-sm text-gray-600 mb-4">
                            Select the account templates this company should have.
                            Existing accounts will not be deleted when unchecked.
                        </p>

                        <div class="border rounded-lg p-4 max-h-96 overflow-y-auto">

                            @forelse($accountTemplates as $template)

                                <label class="flex items-center gap-3 py-2 border-b last:border-b-0">

                                    <input type="checkbox"
                                           name="accounts[]"
                                           value="{{ $template->id }}"
                                           class="rounded"
                                           @checked(
                                               in_array(
                                                   (int) $template->account_code,
                                                   $selectedAccountCodes,
                                                   true
                                               )
                                           )>

                                    <span>
                                        <strong>{{ $template->account_code }}</strong>
                                        — {{ $template->account_name }}
                                    </span>

                                </label>

                            @empty

                                <p class="text-gray-500">
                                    No account templates available.
                                </p>

                            @endforelse

                        </div>

                        @error('accounts')
                            <p class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('accounts.*')
                            <p class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    {{-- Actions --}}
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