@extends('layouts.app')

@section('title','Transactions')

@section('page-title','Transactions')

@section('page-subtitle','Manage your company transactions')

    @section('header')
<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
       Transactions       
    </h2>

    <a href="{{ route('transactions.create') }}"
       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        + Add Transaction
    </a>
</div>
@endsection
@section('content')
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg overflow-hidden">

                <table class="min-w-full border-collapse">

                    <thead class="bg-gray-100">

                        <tr>

                            <th class="border px-4 py-3 text-left">Voucher</th>

                            <th class="border px-4 py-3 text-left">Date</th>

                            <th class="border px-4 py-3 text-left">Company</th>

                            <th class="border px-4 py-3 text-left">Account</th>

                            <th class="border px-4 py-3 text-left">Type</th>

                            <th class="border px-4 py-3 text-right">Amount</th>

                            <th class="border px-4 py-3 text-left">Created By</th>

                            <th class="border px-4 py-3 text-center">Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($transactions as $transaction)

                            <tr>

                                <td class="border px-4 py-3">
                                    {{ $transaction->voucher_no }}
                                </td>

                                <td class="border px-4 py-3">
                                    {{ $transaction->transaction_date }}
                                </td>

                                <td class="border px-4 py-3">
                                    {{ $transaction->company->company_name }}
                                </td>

                               <td class="border px-4 py-3">

    @foreach($transaction->details as $detail)

        {{ $detail->account->account_name ?? '' }}

        @if(!$loop->last)
            <br>
        @endif

    @endforeach

</td>

                                <td class="border px-4 py-3">

                                    @if($transaction->transaction_type=='Income')

                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded">
                                            Income
                                        </span>

                                    @elseif($transaction->transaction_type=='Expense')

                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded">
                                            Expense
                                        </span>

                                    @else

                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">
                                            Journal
                                        </span>

                                    @endif

                                </td>

                                <td class="border px-4 py-3 text-right">
                                    ৳ {{ number_format($transaction->amount,2) }}
                                </td>

                                <td class="border px-4 py-3">
                                    {{ $transaction->user->name ?? 'System' }}
                                </td>

                                <td class="border px-4 py-3 text-center">

                                    <a href="{{ route('transactions.edit',$transaction->id) }}"
                                       class="text-blue-600 hover:underline">
                                        Edit
                                    </a>

                                    |

                                    <form action="{{ route('transactions.destroy',$transaction->id) }}"
                                          method="POST"
                                          class="inline">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            onclick="return confirm('Delete this transaction?')"
                                            class="text-red-600 hover:underline">

                                            Delete

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8"
                                    class="text-center py-8">

                                    No Transactions Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-5">

                {{ $transactions->links() }}

            </div>

        </div>
    </div>

@endsection