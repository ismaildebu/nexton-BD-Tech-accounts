<x-app-layout>

<x-slot name="header">
    <h2 class="text-2xl font-semibold">
        Balance Sheet
    </h2>
</x-slot>

<div class="py-8">
<div class="max-w-7xl mx-auto">

<div class="bg-white rounded shadow p-6">

@php

$totalAssets=0;
$totalLiabilities=0;
$totalEquity=0;

$totalIncome=0;
$totalExpense=0;

@endphp

<h3 class="text-xl font-bold mb-2">
Assets
</h3>

<table class="w-full border mb-8">

<tr class="bg-gray-100">
<th class="border p-2">Code</th>
<th class="border p-2">Account</th>
<th class="border p-2 text-right">Balance</th>
</tr>

@foreach($assets as $account)

@php

$balance=$account->ledgerEntries->sum('debit')
          -$account->ledgerEntries->sum('credit');

$totalAssets+=$balance;

@endphp

<tr>

<td class="border p-2">
{{ $account->account_code }}
</td>

<td class="border p-2">
{{ $account->account_name }}
</td>

<td class="border p-2 text-right">
{{ number_format($balance,2) }}
</td>

</tr>

@endforeach

<tr class="font-bold bg-green-100">
<td colspan="2" class="border p-2">
Total Assets
</td>
<td class="border p-2 text-right">
{{ number_format($totalAssets,2) }}
</td>
</tr>

</table>

<h3 class="text-xl font-bold mb-2">
Liabilities
</h3>

<table class="w-full border mb-8">

<tr class="bg-gray-100">
<th class="border p-2">Code</th>
<th class="border p-2">Account</th>
<th class="border p-2 text-right">Balance</th>
</tr>

@foreach($liabilities as $account)

@php

$balance=$account->ledgerEntries->sum('credit')
          -$account->ledgerEntries->sum('debit');

$totalLiabilities+=$balance;

@endphp

<tr>

<td class="border p-2">{{ $account->account_code }}</td>

<td class="border p-2">{{ $account->account_name }}</td>

<td class="border p-2 text-right">{{ number_format($balance,2) }}</td>

</tr>

@endforeach

<tr class="font-bold bg-yellow-100">
<td colspan="2" class="border p-2">
Total Liabilities
</td>

<td class="border p-2 text-right">
{{ number_format($totalLiabilities,2) }}
</td>

</tr>

</table>

@foreach($income as $a)
@php $totalIncome += $a->ledgerEntries->sum('credit'); @endphp
@endforeach

@foreach($expense as $a)
@php $totalExpense += $a->ledgerEntries->sum('debit'); @endphp
@endforeach

@php

$currentProfit=$totalIncome-$totalExpense;

@endphp

<h3 class="text-xl font-bold mb-2">
Equity
</h3>

<table class="w-full border">

<tr class="bg-gray-100">
<th class="border p-2">Code</th>
<th class="border p-2">Account</th>
<th class="border p-2 text-right">Balance</th>
</tr>

@foreach($equity as $account)

@php

$balance=$account->ledgerEntries->sum('credit')
          -$account->ledgerEntries->sum('debit');

$totalEquity+=$balance;

@endphp

<tr>

<td class="border p-2">{{ $account->account_code }}</td>

<td class="border p-2">{{ $account->account_name }}</td>

<td class="border p-2 text-right">{{ number_format($balance,2) }}</td>

</tr>

@endforeach

<tr>

<td colspan="2" class="border p-2">

Current Year Profit

</td>

<td class="border p-2 text-right">

{{ number_format($currentProfit,2) }}

</td>

</tr>

<tr class="font-bold bg-blue-100">

<td colspan="2" class="border p-2">

Total Equity

</td>

<td class="border p-2 text-right">

{{ number_format($totalEquity+$currentProfit,2) }}

</td>

</tr>

</table>

<hr class="my-6">

<h2 class="text-xl font-bold">

Assets :

{{ number_format($totalAssets,2) }}

</h2>

<h2 class="text-xl font-bold">

Liabilities + Equity :

{{ number_format($totalLiabilities+$totalEquity+$currentProfit,2) }}

</h2>

@if(abs($totalAssets-($totalLiabilities+$totalEquity+$currentProfit))<0.01)

<div class="mt-4 text-green-700 font-bold text-xl">

✓ Balance Sheet Matched

</div>

@else

<div class="mt-4 text-red-700 font-bold text-xl">

✗ Balance Sheet Not Matched

</div>

@endif

</div>

</div>

</div>

</x-app-layout>