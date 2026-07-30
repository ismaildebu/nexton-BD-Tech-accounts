@extends('layouts.app')

@section('title','VoucherTypes')

@section('page-title','VoucherTypes')

@section('page-subtitle','Manage your company Vouchers')

    @section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Voucher Types
    </h2>

    <a href="{{ route('voucher-types.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Voucher Type
    </a>

</div>

@endsection

@section('content')
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('voucher-types.store') }}" method="POST">

                    @csrf

                    <!-- Company -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Company
                        </label>

                        <select name="company_id"
                                class="w-full border rounded px-3 py-2"
                                required>

                            <option value="">-- Select Company --</option>

                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">
                                    {{ $company->company_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <!-- Voucher Name -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Voucher Name
                        </label>

                        <input type="text"
                               name="voucher_name"
                               value="{{ old('voucher_name') }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                    </div>

                    <!-- Voucher Code -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Voucher Code
                        </label>

                        <input type="text"
                               name="voucher_code"
                               value="{{ old('voucher_code') }}"
                               placeholder="JV / PV / RV"
                               class="w-full border rounded px-3 py-2"
                               required>
                    </div>

                    <!-- Active -->
                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   checked>

                            <span class="ml-2">Active</span>
                        </label>
                    </div>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                        Save Voucher Type
                    </button>

                    <a href="{{ route('voucher-types.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded ml-2">
                        Back
                    </a>

                </form>

            </div>

        </div>
    </div>

@endsection