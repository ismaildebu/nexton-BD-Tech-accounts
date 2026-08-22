@extends('layouts.app')

@section('title', 'Create Voucher')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Voucher</h1>
            <nav class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('vouchers.index') }}" class="hover:text-blue-600 transition-colors">Vouchers</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>New Voucher</span>
            </nav>
        </div>
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
        'transaction'    => null,
        'voucherTypes'   => $voucherTypes,
        'financialYears' => $financialYears,
        'accounts'       => $accounts,
        'formAction'     => route('vouchers.store'),
        'formMethod'     => 'POST',
    ])

</div>
@endsection