@extends('layouts.app')

@section('title', 'Edit Voucher — ' . $transaction->voucher_number)

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Voucher</h1>
            <nav class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('vouchers.index') }}" class="hover:text-blue-600 transition-colors">Vouchers</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <a href="{{ route('vouchers.show', $transaction) }}" class="hover:text-blue-600 transition-colors">
                    {{ $transaction->voucher_number }}
                </a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>Edit</span>
            </nav>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-medium rounded-full">
            <i class="bi bi-pencil-square"></i>
            Draft
        </span>
    </div>

    {{-- Flash Messages --}}
    @include('partials.flash')

    {{-- Validation Summary --}}
    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm text-red-600 dark:text-red-400">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @include('vouchers.partials.form', [
        'transaction'    => $transaction,
        'voucherTypes'   => $voucherTypes,
        'financialYears' => $financialYears,
        'accounts'       => $accounts,
        'formAction'     => route('vouchers.update', $transaction),
        'formMethod'     => 'PUT',
    ])

</div>
@endsection