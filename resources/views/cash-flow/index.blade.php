@extends('layouts.app')

@section('title','Cash Flow')

@section('page-title','Cash Flow')

@section('page-subtitle','Manage your company Cash Flow Statement')


@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Cash Flow Statement
    </h2>

</div>

@endsection


@section('content')

<div class="py-8">

    <div class="max-w-5xl mx-auto mb-5">

        <form method="GET" action="{{ route('cash-flow.index') }}" class="bg-white shadow rounded-lg p-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">
                        From Date
                    </label>
                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        value="{{ old('from_date', $fromDate) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">
                        To Date
                    </label>
                    <input
                        type="date"
                        id="to_date"
                        name="to_date"
                        value="{{ old('to_date', $toDate) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700"
                    >
                        Apply Filter
                    </button>

                    <a
                        href="{{ route('cash-flow.index') }}"
                        class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 font-medium hover:bg-gray-200"
                    >
                        Reset
                    </a>
                </div>

            </div>

        </form>

    </div>

    <div class="max-w-5xl mx-auto">

        <div class="max-w-5xl mx-auto">

            <div class="bg-white shadow rounded-lg p-6">

                <table class="w-full border">

                    <tbody>

                        <tr class="bg-gray-100 font-bold">
                            <td class="border p-3">
                                Opening Cash Balance
                            </td>

                            <td class="border p-3 text-right">
                                {{ number_format($openingBalance,2) }}
                            </td>
                        </tr>

                        <tr class="bg-green-100 font-bold">
                            <td colspan="2" class="border p-3">
                                Operating Activities
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Cash Received
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($operatingIn,2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Cash Paid
                            </td>

                            <td class="border p-2 text-right">
                                ({{ number_format($operatingOut,2) }})
                            </td>
                        </tr>

                        <tr class="font-semibold">
                            <td class="border p-2">
                                Net Operating Cash Flow
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($operatingIn-$operatingOut,2) }}
                            </td>
                        </tr>

                        <tr class="bg-yellow-100 font-bold">
                            <td colspan="2" class="border p-3">
                                Investing Activities
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Asset Sales
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($investingIn,2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Asset Purchases
                            </td>

                            <td class="border p-2 text-right">
                                ({{ number_format($investingOut,2) }})
                            </td>
                        </tr>

                        <tr class="font-semibold">
                            <td class="border p-2">
                                Net Investing Cash Flow
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($investingIn-$investingOut,2) }}
                            </td>
                        </tr>

                        <tr class="bg-purple-100 font-bold">
                            <td colspan="2" class="border p-3">
                                Financing Activities
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Capital / Loan Received
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($financingIn,2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="border p-2">
                                Loan Payment / Drawings
                            </td>

                            <td class="border p-2 text-right">
                                ({{ number_format($financingOut,2) }})
                            </td>
                        </tr>

                        <tr class="font-semibold">
                            <td class="border p-2">
                                Net Financing Cash Flow
                            </td>

                            <td class="border p-2 text-right">
                                {{ number_format($financingIn-$financingOut,2) }}
                            </td>
                        </tr>

                        <tr class="bg-blue-100 font-bold text-lg">
                            <td class="border p-3">
                                Closing Cash Balance
                            </td>

                            <td class="border p-3 text-right">
                                {{ number_format($closingBalance,2) }}
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection