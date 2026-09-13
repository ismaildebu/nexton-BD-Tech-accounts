@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                Salaries
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage employee salaries and salary payments.
            </p>
        </div>

        <a href="{{ route('salaries.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
            + Add Salary
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded-md bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">

            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                        Employee
                    </th>

                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                        Month
                    </th>

                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                        Basic
                    </th>

                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                        Allowances
                    </th>

                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                        Deductions
                    </th>

                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                        Net Salary
                    </th>

                    <th class="px-4 py-3 text-center font-medium text-gray-600">
                        Status
                    </th>

                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">

                @forelse ($salaries as $salary)

                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3 text-gray-800">
                            {{ $salary->employee?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            {{ $salary->month }}/{{ $salary->year }}
                        </td>

                        <td class="px-4 py-3 text-right text-gray-800">
                            {{ number_format((float) $salary->basic_salary, 2) }}
                        </td>

                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ number_format((float) $salary->allowances, 2) }}
                        </td>

                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ number_format((float) $salary->deductions, 2) }}
                        </td>

                        <td class="px-4 py-3 text-right font-semibold text-gray-800">
                            {{ number_format((float) $salary->net_salary, 2) }}
                        </td>

                        <td class="px-4 py-3 text-center">

                            @if ($salary->status === 'paid')

                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                    Paid
                                </span>

                                @if ($salary->paid_date)
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $salary->paid_date->format('d M Y') }}
                                    </div>
                                @endif

                            @else

                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td class="px-4 py-3 text-right">

                            <div class="flex flex-col items-end gap-2">

                                <div class="space-x-2 whitespace-nowrap">
                                    <a href="{{ route('salaries.show', $salary) }}"
                                       class="text-blue-600 hover:underline">
                                        View
                                    </a>

                                    @if ($salary->status !== 'paid')
                                        <form action="{{ route('salaries.destroy', $salary) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Delete this salary record?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="text-red-600 hover:underline">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if ($salary->status !== 'paid' && $salary->transaction_id === null)

                                    <form action="{{ route('salaries.mark-paid', $salary) }}"
                                          method="POST"
                                          class="flex flex-col sm:flex-row items-end gap-2">

                                        @csrf

                                        <select name="payment_account_id"
                                                required
                                                class="w-64 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                                            <option value="">
                                                Select payment account
                                            </option>

                                            @if ($cashAccount)
                                                <option value="{{ $cashAccount->id }}">
                                                    Cash — {{ $cashAccount->account_name }}
                                                </option>
                                            @endif

                                            @if ($bankAccounts->isNotEmpty())
                                                <optgroup label="Bank Accounts">

                                                    @foreach ($bankAccounts as $bankAccount)

                                                        @php
                                                            $maskedAccountNumber = '—';

                                                            if (!empty($bankAccount->account_number)) {
                                                                $number = (string) $bankAccount->account_number;

                                                                $maskedAccountNumber =
                                                                    strlen($number) > 4
                                                                        ? '****' . substr($number, -4)
                                                                        : $number;
                                                            }
                                                        @endphp

                                                        <option value="{{ $bankAccount->account_id }}">
                                                            {{ $bankAccount->bank_name }}
                                                            @if ($bankAccount->account_name)
                                                                — {{ $bankAccount->account_name }}
                                                            @endif
                                                            — A/C {{ $maskedAccountNumber }}
                                                        </option>

                                                    @endforeach

                                                </optgroup>
                                            @endif

                                        </select>

                                        <button type="submit"
                                                onclick="return confirm('Mark this salary as paid and create the Payment Voucher?');"
                                                class="inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow transition">
                                            Mark Paid
                                        </button>

                                    </form>

                                @elseif ($salary->status === 'paid')

                                    <div class="text-xs text-gray-500">
                                        @if ($salary->paymentAccount)
                                            Paid from:
                                            <span class="font-medium text-gray-700">
                                                {{ $salary->paymentAccount->account_name }}
                                            </span>
                                        @endif

                                        @if ($salary->transaction)
                                            <span class="ml-1">
                                                • Voucher:
                                                <span class="font-medium text-gray-700">
                                                    {{ $salary->transaction->voucher_number }}
                                                </span>
                                            </span>
                                        @endif
                                    </div>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="8"
                            class="px-4 py-8 text-center text-gray-400">
                            No salary records found.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $salaries->links() }}
    </div>

</div>
@endsection