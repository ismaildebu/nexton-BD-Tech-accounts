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
<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Cash Flow Statement
    </h2>

</div>

@endsection


@section('content')


<div class="py-8">

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