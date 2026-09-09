@extends('layouts.app')

@section('title', 'Add Bank Account Form')

@section('page-title', 'Add Bank Account')

@section('page-subtitle', 'Create a new company bank account')

@section('content')

<div class="container mx-auto max-w-3xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            Add Bank Account
        </h1>

        <p class="text-gray-500 mt-1">
            Create a new bank account for the current company.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc ml-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bank-accounts.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 space-y-5">

            {{-- Bank Name --}}
            <div>
                <label class="block font-medium mb-2">
                    Bank Name
                </label>

                <input
                    type="text"
                    name="bank_name"
                    value="{{ old('bank_name') }}"
                    class="w-full border rounded-lg p-2"
                    required
                    autofocus
                >

                @error('bank_name')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Account Name --}}
            <div>
                <label class="block font-medium mb-2">
                    Account Name
                </label>

                <input
                    type="text"
                    name="account_name"
                    value="{{ old('account_name') }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('account_name')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Account Number --}}
            <div>
                <label class="block font-medium mb-2">
                    Account Number
                </label>

                <input
                    type="text"
                    name="account_number"
                    value="{{ old('account_number') }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                @error('account_number')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Branch --}}
            <div>
                <label class="block font-medium mb-2">
                    Branch Name
                </label>

                <input
                    type="text"
                    name="branch_name"
                    value="{{ old('branch_name') }}"
                    class="w-full border rounded-lg p-2"
                >

                @error('branch_name')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Opening Balance --}}
            <div>
                <label class="block font-medium mb-2">
                    Opening Balance
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="balance"
                    value="{{ old('balance', '0') }}"
                    class="w-full border rounded-lg p-2"
                    required
                >

                <p class="text-xs text-gray-500 mt-1">
                    This value will also become the Accounting Account opening balance.
                </p>

                @error('balance')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="pt-2 flex gap-3">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
                >
                    Save Bank Account
                </button>

                <a
                    href="{{ route('bank-accounts.index') }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg"
                >
                    Cancel
                </a>

            </div>

        </div>
    </form>

</div>

@endsection