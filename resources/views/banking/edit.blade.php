@extends('layouts.app')

@section('title','Edit Bank Account')

@section('page-title','Edit Bank Account')

@section('page-subtitle','Update bank account details')


@section('content')

<div class="container mx-auto max-w-3xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            Edit Bank Account
        </h1>

        <p class="text-gray-500">
            Update the details of this bank account.
        </p>
    </div>

    <form action="{{ route('bank-accounts.update', $bankAccount->id) }}" method="POST">

        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6 space-y-5">

            <div>
                <label class="block font-medium mb-2">
                    Bank Name
                </label>

                <input
                    type="text"
                    name="bank_name"
                    value="{{ old('bank_name', $bankAccount->bank_name) }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('bank_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Account Name
                </label>

                <input
                    type="text"
                    name="account_name"
                    value="{{ old('account_name', $bankAccount->account_name) }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('account_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Account Number
                </label>

                <input
                    type="text"
                    name="account_number"
                    value="{{ old('account_number', $bankAccount->account_number) }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('account_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Branch Name
                </label>

                <input
                    type="text"
                    name="branch_name"
                    value="{{ old('branch_name', $bankAccount->branch_name) }}"
                    class="w-full border rounded-lg p-2"
                >
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Balance
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="balance"
                    value="{{ old('balance', $bankAccount->balance) }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('balance')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="is_active"
                    name="is_active"
                    value="1"
                    {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }}
                    class="rounded border-gray-300"
                >
                <label for="is_active" class="font-medium">
                    Active
                </label>
            </div>

            <div class="pt-2 flex gap-3">
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg"
                >
                    Update Bank Account
                </button>

                <a
                    href="{{ route('bank-accounts.index') }}"
                    class="bg-gray-100 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-200"
                >
                    Cancel
                </a>
            </div>

        </div>

    </form>

</div>

@endsection