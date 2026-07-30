@extends('layouts.app')

@section('title','Profit & Loss')

@section('page-title','Profit & Loss')

@section('page-subtitle','Manage your company Profit & Loss Statement')@section('header')

@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Title
    </h2>

</div>

@endsection

@section('content')
<div class="py-8">

<div class="max-w-7xl mx-auto">

<div class="bg-white shadow rounded p-6">

@php

$totalIncome=0;
$totalExpense=0;

@endphp

<h3 class="text-lg font-bold mb-3">
Income
</h3>

<table class="w-full border mb-8">

<thead>

<tr>

<th class="border p-2">
Code
</th>

<th class="border p-2">
Account
</th>

<th class="border p-2 text-right">
Amount
</th>

</tr>

</thead>

<tbody>

@foreach($incomeAccounts as $account)

@php

$amount=$account->ledgerEntries->sum('credit');

$totalIncome+=$amount;

@endphp

<tr>

<td class="border p-2">

{{ $account->account_code }}

</td>

<td class="border p-2">

{{ $account->account_name }}

</td>

<td class="border p-2 text-right">

{{ number_format($amount,2) }}

</td>

</tr>

@endforeach

<tr class="font-bold bg-green-100">

<td colspan="2" class="border p-2">

Total Income

</td>

<td class="border p-2 text-right">

{{ number_format($totalIncome,2) }}

</td>

</tr>

</tbody>

</table>



<h3 class="text-lg font-bold mb-3">

Expenses

</h3>

<table class="w-full border">

<thead>

<tr>

<th class="border p-2">
Code
</th>

<th class="border p-2">
Account
</th>

<th class="border p-2 text-right">
Amount
</th>

</tr>

</thead>

<tbody>

@foreach($expenseAccounts as $account)

@php

$amount=$account->ledgerEntries->sum('debit');

$totalExpense+=$amount;

@endphp

<tr>

<td class="border p-2">

{{ $account->account_code }}

</td>

<td class="border p-2">

{{ $account->account_name }}

</td>

<td class="border p-2 text-right">

{{ number_format($amount,2) }}

</td>

</tr>

@endforeach

<tr class="font-bold bg-red-100">

<td colspan="2" class="border p-2">

Total Expense

</td>

<td class="border p-2 text-right">

{{ number_format($totalExpense,2) }}

</td>

</tr>

</tbody>

</table>

<hr class="my-6">

<h2 class="text-2xl font-bold">

Net Profit :

{{ number_format($totalIncome-$totalExpense,2) }}

</h2>

</div>

</div>

</div>

@endsection