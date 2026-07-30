@extends('layouts.app')

@section('title', 'Add Account')

@section('page-title', 'Add New Account')

@section('page-subtitle', 'Create a new chart of account')

@section('header')
<div class="flex justify-between items-center">
    <h2 class="font-semibold text-2xl text-gray-800">
        Add New Account
    </h2>

    <a href="{{ route('accounts.index') }}"
       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
        Back
    </a>
</div>
@endsection

@section('content')

<div class="max-w-4xl mx-auto">

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6">

        <form action="{{ route('accounts.store') }}" method="POST">
            @csrf

            {{-- Company --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Company
                </label>

                <select name="company_id"
                        class="w-full border rounded-lg px-3 py-2"
                        required>

                    <option value="">-- Select Company --</option>

                    @foreach($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ old('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->company_name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Account Name --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Account Name
                </label>

                <input
                    type="text"
                    name="account_name"
                    value="{{ old('account_name') }}"
                    class="w-full border rounded-lg px-3 py-2"
                    required>
            </div>

            {{-- Account Type --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Account Type
                </label>

                <select
                    name="account_type"
                    class="w-full border rounded-lg px-3 py-2"
                    required>

                    <option value="">-- Select Type --</option>

                    @foreach(\App\Models\Account::accountTypes() as $type)
                        <option value="{{ $type }}"
                            {{ old('account_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Nature --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Nature
                </label>

                <select
                    name="nature"
                    class="w-full border rounded-lg px-3 py-2"
                    required>

                    <option value="">-- Select Nature --</option>

                    @foreach(\App\Models\Account::accountNatures() as $nature)
                        <option value="{{ $nature }}"
                            {{ old('nature') == $nature ? 'selected' : '' }}>
                            {{ $nature }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Parent Account --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Parent Account
                </label>

                <select
                    name="parent_id"
                    class="w-full border rounded-lg px-3 py-2">

                    <option value="">None</option>

                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}"
                            {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->account_code }} - {{ $parent->account_name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Opening Balance --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Opening Balance
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="opening_balance"
                    value="{{ old('opening_balance',0) }}"
                    class="w-full border rounded-lg px-3 py-2">
            </div>

            {{-- Color --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    Color
                </label>

                <input
                    type="color"
                    name="color"
                    value="{{ old('color','#2563eb') }}"
                    class="h-10 w-24 border rounded">
            </div>

            <div class="flex gap-3">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                    Save Account
                </button>

                <a href="{{ route('accounts.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

@endsection