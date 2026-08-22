@extends('layouts.app')

@section('title','Bank Account Details')

@section('page-title','Bank Account Details')

@section('page-subtitle', $bankAccount->bank_name)

@section('content')

    <div class="py-6">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-bold">
                            {{ $bankAccount->account_name }}
                        </h3>
                        <p class="text-gray-500">{{ $bankAccount->bank_name }}</p>
                    </div>

                    <a href="{{ route('bank-accounts.edit', $bankAccount->id) }}"
                       class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">
                        Edit
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Account Number</p>
                        <p class="font-medium">{{ $bankAccount->account_number }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Branch</p>
                        <p class="font-medium">{{ $bankAccount->branch_name ?? '-' }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Balance</p>
                        <p class="font-medium">৳{{ number_format($bankAccount->balance, 2) }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs text-slate-500">Status</p>
                        <p class="font-medium">
                            @if($bankAccount->is_active)
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>

                <a href="{{ route('bank-accounts.index') }}"
                   class="inline-block mt-6 text-sm text-blue-600 hover:underline">
                    ← Back to Bank Accounts
                </a>

            </div>

        </div>

    </div>

@endsection