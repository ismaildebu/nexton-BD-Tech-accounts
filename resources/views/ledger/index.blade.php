@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">General Ledger</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Account-wise transaction history</p>
        </div>
        <a href="{{ route('vouchers.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            New Voucher
        </a>
    </div>

    {{-- Flash Messages --}}
    @include('partials.flash')

    {{-- Filter --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <form method="GET" action="{{ route('ledger.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Account <span class="text-red-500">*</span>
                    </label>
                    <select name="account_id" required
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">— Select Account —</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>
                                {{ $account->account_code }} — {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Financial Year</label>
                    <select name="financial_year_id"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">All Years</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>
                                {{ $fy->year_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">From Date</label>
                    <input type="date"
                           name="from_date"
                           value="{{ request('from_date') }}"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">To Date</label>
                    <input type="date"
                           name="to_date"
                           value="{{ request('to_date') }}"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

            </div>

            <div class="flex items-center gap-3 mt-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-search"></i>
                    Show Ledger
                </button>
                <a href="{{ route('ledger.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-x-lg"></i>
                    Reset
                </a>
                @if($selectedAccount && $ledger->isNotEmpty())
                    <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="bi bi-printer"></i>
                        Print
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Selected Account Info --}}
    @if($selectedAccount)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium uppercase tracking-wide">Selected Account</p>
                    <p class="text-lg font-bold text-blue-900 dark:text-blue-100 mt-0.5">
                        {{ $selectedAccount->account_code }} — {{ $selectedAccount->account_name }}
                    </p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-0.5">
                        Type: {{ $selectedAccount->account_type }} |
                        Nature: {{ ucfirst($selectedAccount->nature ?? '—') }}
                    </p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium uppercase">Total Debit</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white font-mono">
                            {{ number_format($runningDebit, 2) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium uppercase">Total Credit</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white font-mono">
                            {{ number_format($runningCredit, 2) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium uppercase">Balance</p>
                        <p class="text-lg font-bold font-mono {{ ($runningDebit - $runningCredit) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format(abs($runningDebit - $runningCredit), 2) }}
                            {{ ($runningDebit - $runningCredit) >= 0 ? 'Dr' : 'Cr' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Ledger Table --}}
    @if($selectedAccount)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-journal-text text-blue-600"></i>
                    Ledger Entries
                    <span class="text-sm font-normal text-gray-500">({{ $ledger->count() }} records)</span>
                </h2>
            </div>

            @if($ledger->isEmpty())
                <div class="flex flex-col items-center gap-3 py-16">
                    <i class="bi bi-journal-x text-5xl text-gray-300 dark:text-gray-600"></i>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No ledger entries found</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Try adjusting the date range or create a new voucher</p>
                </div>
            @else
                @include('ledger.partials.ledger-table', ['ledger' => $ledger])
            @endif

        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col items-center gap-3 py-20">
                <i class="bi bi-book text-6xl text-gray-300 dark:text-gray-600"></i>
                <p class="text-gray-600 dark:text-gray-400 font-medium text-lg">Select an account to view ledger</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">Choose an account from the filter above</p>
            </div>
        </div>
    @endif

</div>
@endsection