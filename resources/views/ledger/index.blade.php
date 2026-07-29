@extends('layouts.app')

@section('title','Accounts')

@section('page-title','Chart of Accounts')

@section('page-subtitle','Manage your company accounts')

@section('content')

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800">
                General Ledger
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <form method="GET" action="{{ route('ledger.index') }}">

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        <div>
                            <label class="font-semibold">
                                Account
                            </label>

                            <select
                                name="account_id"
                                class="w-full border rounded px-3 py-2">

                                <option value="">
                                    Select Account
                                </option>

                                @foreach($accounts as $account)

                                    <option
                                        value="{{ $account->id }}"
                                        {{ request('account_id') == $account->id ? 'selected' : '' }}>

                                        {{ $account->account_code }}
                                        -
                                        {{ $account->account_name }}

                                    </option>

                                @endforeach

                            </select>
                        </div>

                        <div>
    <label class="font-semibold">
        From Date
    </label>

    <input
        type="date"
        name="from_date"
        value="{{ request('from_date') }}"
        class="w-full border rounded px-3 py-2">
</div>


<div>
    <label class="font-semibold">
        To Date
    </label>

    <input
        type="date"
        name="to_date"
        value="{{ request('to_date') }}"
        class="w-full border rounded px-3 py-2">
</div>

                        <div class="flex items-end">

                            <button
                                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">

                                Search Ledger

                            </button>

                        </div>

                    </div>

                </form>

                
@if($selectedAccount)

<div class="bg-gray-100 p-4 mb-4 rounded">

    <h3 class="text-xl font-bold">
        {{ $selectedAccount->account_name }}
    </h3>

    <p>
        Account Code:
        {{ $selectedAccount->account_code }}
    </p>

    <p>
        Period:
        {{ request('from_date') ?? 'Start' }}
        -
        {{ request('to_date') ?? 'End' }}
    </p>

</div>

@endif

                <hr class="my-6">

                <table class="w-full border">

                    <thead class="bg-gray-100">

                        <tr>

                            <th class="border p-2">Date</th>

                            <th class="border p-2">Voucher</th>

                            <th class="border p-2">Description</th>

                            <th class="border p-2 text-right">
                                Debit
                            </th>

                            <th class="border p-2 text-right">
                                Credit
                                </th>

                                <th class="border p-2 text-right">
                                 Balance
                                </th>
                            

                        </tr>

                    </thead>

                    <tbody>

    @php
        $balance = 0;
    @endphp

    @forelse($ledger as $row)
                    

                            <tr>

                                <td class="border p-2">
                                    {{ $row->entry_date }}
                                </td>

                                <td class="border p-2">
                                    {{ $row->transaction->voucher_no }}
                                </td>

                                <td class="border p-2">
                                    {{ $row->description }}
                                </td>

                                <td class="border p-2 text-right">
                                    {{ number_format($row->debit,2) }}
                                </td>

                                <td class="border p-2 text-right">
                                    {{ number_format($row->credit,2) }}
                                </td>
                                <td class="border p-2 text-right">

    @php
        $balance += $row->debit - $row->credit;
    @endphp

    {{ number_format($balance,2) }}

</td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6"
                                    class="text-center p-4">

                                    No Ledger Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    </div>

@endsection