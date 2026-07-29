@extends('layouts.app')

@section('content')

<div class="container mx-auto max-w-3xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            Add Bank Account
        </h1>

        <p class="text-gray-500">
            Create a new bank account for a company.
        </p>
    </div>

    <form action="{{ route('bank-accounts.store') }}" method="POST">

        @csrf

        <div class="bg-white rounded-lg shadow p-6 space-y-5">

            <div>
                <label class="block font-medium mb-2">
                    Company
                </label>

                <select
                    name="company_id"
                    class="w-full border rounded-lg p-2"
                    required
                >
                    <option value="">Select Company</option>

                    @foreach($companies as $company)

                        <option value="{{ $company->id }}">
                            {{ $company->company_name }}
                        </option>

                    @endforeach

                </select>
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Bank Name
                </label>

                <input
                    type="text"
                    name="bank_name"
                    class="w-full border rounded-lg p-2"
                    required
                >
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Account Name
                </label>

                <input
                    type="text"
                    name="account_name"
                    class="w-full border rounded-lg p-2"
                    required
                >
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Account Number
                </label>

                <input
                    type="text"
                    name="account_number"
                    class="w-full border rounded-lg p-2"
                    required
                >
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Opening Balance
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="balance"
                    value="0"
                    class="w-full border rounded-lg p-2"
                    required
                >
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg"
                >
                    Save Bank Account
                </button>
            </div>

        </div>

    </form>

</div>

@endsection