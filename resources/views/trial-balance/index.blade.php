@extends('layouts.app')

@section('title','Accounts')

@section('page-title','Chart of Accounts')

@section('page-subtitle','Manage your company accounts')

@section('content')

    <x-slot name="header">
        <h2 class="font-semibold text-2xl">
            Trial Balance
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6 overflow-x-auto">

                @php
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp

                <table class="min-w-full border border-gray-300">

                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left">Code</th>
                            <th class="border px-3 py-2 text-left">Account Name</th>
                            <th class="border px-3 py-2 text-left">Type</th>
                            <th class="border px-3 py-2 text-right">Debit</th>
                            <th class="border px-3 py-2 text-right">Credit</th>
                            <th class="border px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse($accounts as $account)

                        @php
                            $debit = $account->debit_total;
                            $credit = $account->credit_total;
                            $balance = $account->balance;

                            $totalDebit += $debit;
                            $totalCredit += $credit;
                        @endphp

                        <tr>
                            <td class="border px-3 py-2">
                                {{ $account->account_code }}
                            </td>

                            <td class="border px-3 py-2">
                                {{ $account->account_name }}
                            </td>

                            <td class="border px-3 py-2">
                                {{ $account->account_type }}
                            </td>

                            <td class="border px-3 py-2 text-right">
                                {{ number_format($debit,2) }}
                            </td>

                            <td class="border px-3 py-2 text-right">
                                {{ number_format($credit,2) }}
                            </td>

                            <td class="border px-3 py-2 text-right">
                                {{ number_format($balance,2) }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="border py-6 text-center">
                                No Accounts Found
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="bg-gray-100 font-bold">

                            <td colspan="3" class="border px-3 py-2 text-right">
                                Total
                            </td>

                            <td class="border px-3 py-2 text-right">
                                {{ number_format($totalDebit,2) }}
                            </td>

                            <td class="border px-3 py-2 text-right">
                                {{ number_format($totalCredit,2) }}
                            </td>

                            <td class="border"></td>

                        </tr>

                        <tr>

                            <td colspan="6" class="border px-3 py-3">

                                @if($totalDebit == $totalCredit)

                                    <span class="text-green-600 font-semibold">
                                        ✓ Trial Balance Matched
                                    </span>

                                @else

                                    <span class="text-red-600 font-semibold">
                                        ✗ Trial Balance Not Matched
                                    </span>

                                @endif

                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>
    </div>

@endsection