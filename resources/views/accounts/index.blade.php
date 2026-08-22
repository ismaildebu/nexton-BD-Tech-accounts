@extends('layouts.app')

@section('title','Accounts')

@section('page-title','Chart of Accounts')

@section('page-subtitle','Manage your company accounts')



@section('content')
@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="font-semibold text-xl text-slate-700">Chart of Accounts</h2>
    <a href="{{ route('accounts.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        + Add Account
    </a>
</div>


    <div class="py-8">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">


                @if(session('success'))

                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">

                        {{ session('success') }}

                    </div>

                @endif



                <table class="w-full border border-gray-300">

                    <thead class="bg-gray-100">

                        <tr>

                            <th class="border p-2 text-left">
                                Code
                            </th>

                            <th class="border p-2 text-left">
                                Account Name
                            </th>

                            <th class="border p-2 text-left">
                                Type
                            </th>

                            <th class="border p-2 text-left">
                                Company
                            </th>

                            <th class="border p-2 text-left">
                                Balance
                            </th>

                            
                        <th class="border p-2 text-center">
                            Action
                            </th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($accounts as $account)

                            <tr>

                                <td class="border p-2">
                                    {{ $account->account_code }}
                                </td>


                                <td class="border p-2 font-semibold">
                                    {{ $account->account_name }}
                                </td>


                                <td class="border p-2">
                                    {{ $account->account_type }}
                                </td>


                                <td class="border p-2">
                                    {{ $account->company->company_name ?? '' }}
                                </td>


                                <td class="border p-2">

                                    {{ number_format($account->opening_balance,2) }}

                                    {{ $account->balance_type }}

                                </td>

                                

<td class="border p-2 text-center">
    <a href="{{ route('accounts.show', $account) }}"
   class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
    View
</a>

    <a href="{{ route('accounts.edit', $account) }}"
       class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
        Edit
    </a>

    <form action="{{ route('accounts.destroy', $account) }}"
          method="POST"
          class="inline ml-2">

        @csrf
        @method('DELETE')

        <button type="submit"
                onclick="return confirm('Are you sure you want to delete this account?')"
                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
            Delete
        </button>

    </form>

</td>
                            </tr>


                        @empty

                            <tr>

                                <td colspan="6"
                                    class="border p-4 text-center">

                                    No Account Found

                                </td>

                            </tr>

                        @endforelse


                    </tbody>


                </table>


            </div>

        </div>

    </div>



@endsection