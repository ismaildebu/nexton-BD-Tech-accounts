@extends('layouts.app')

@section('page-title', 'Voucher Type')
@section('page-subtitle', 'View voucher type details')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white shadow rounded-lg p-6">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800">
                        {{ $voucherType->name }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Voucher Type Details
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('voucher-types.edit', $voucherType) }}"
                       class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                        Edit
                    </a>

                    <a href="{{ route('voucher-types.index') }}"
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Back
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Company</p>
                    <p class="font-medium text-gray-800 mt-1">
                        {{ $voucherType->company->company_name ?? '-' }}
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Voucher Name</p>
                    <p class="font-medium text-gray-800 mt-1">
                        {{ $voucherType->name }}
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Code</p>
                    <p class="font-medium text-gray-800 mt-1">
                        {{ $voucherType->code }}
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Nature</p>
                    <p class="font-medium text-gray-800 mt-1">
                        {{ $voucherType->nature_label }}
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="font-medium mt-1 {{ $voucherType->is_active ? 'text-green-600' : 'text-red-600' }}">
                        {{ $voucherType->is_active ? 'Active' : 'Inactive' }}
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <p class="text-sm text-gray-500">Created At</p>
                    <p class="font-medium text-gray-800 mt-1">
                        {{ $voucherType->created_at?->format('d M Y, h:i A') ?? '-' }}
                    </p>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection