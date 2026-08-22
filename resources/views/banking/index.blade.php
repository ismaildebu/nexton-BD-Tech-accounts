@extends('layouts.app')

@section('title','Bank Accounts')

@section('page-title','Bank Accounts')

@section('page-subtitle','Manage company bank accounts and balances')

@section('content')
<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Bank Accounts
    </h2>

    <a href="{{ route('bank-accounts.create') }}"
       class="bg-emerald-600 text-white px-5 py-2 rounded-lg hover:bg-emerald-700">

        + Add Bank Account

    </a>

</div>

@endsection



@section('content')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Bank Accounts
    </h2>

    <a href="{{ route('bank-accounts.create') }}"
       class="bg-emerald-600 text-white px-5 py-2 rounded-lg hover:bg-emerald-700">
        + Add Bank Account
    </a>

</div>

@endsection

@section('content')


        <a href="{{ route('bank-accounts.create') }}"
           class="bg-emerald-600 text-white px-5 py-2 rounded-lg hover:bg-emerald-700">
            + Add Bank Account
        </a>

    </div>



    {{-- Success Message --}}
    @if(session('success'))

        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>

    @endif



    {{-- Bank Account Table --}}

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full">

            <thead class="bg-slate-100">

                <tr>

                    <th class="text-left p-4">
                        Bank Name
                    </th>

                    <th class="text-left p-4">
                        Account Name
                    </th>

                    <th class="text-left p-4">
                        Account Number
                    </th>

                    <th class="text-left p-4">
                        Branch
                    </th>

                    <th class="text-right p-4">
                        Balance
                    </th>

                    <th class="text-center p-4">
                        Status
                    </th>

                    <th class="text-center p-4">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


            @forelse($bankAccounts as $account)


                <tr class="border-t">


                    <td class="p-4 font-medium">
                        {{ $account->bank_name }}
                    </td>


                    <td class="p-4">
                        {{ $account->account_name }}
                    </td>


                    <td class="p-4">
                        {{ $account->account_number }}
                    </td>


                    <td class="p-4">
                        {{ $account->branch_name ?? '-' }}
                    </td>


                    <td class="p-4 text-right font-semibold">

                        ৳{{ number_format($account->balance,2) }}

                    </td>


                    <td class="p-4 text-center">

                        @if($account->is_active)

                            <span class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-700">
                                Active
                            </span>

                        @else

                            <span class="px-3 py-1 rounded-full text-sm bg-red-100 text-red-700">
                                Inactive
                            </span>

                        @endif

                    </td>


                    <td class="p-4 text-center">


                        <a href="{{ route('bank-accounts.edit',$account->id) }}"
                           class="text-blue-600 mr-3">

                            Edit

                        </a>



                        <form action="{{ route('bank-accounts.destroy',$account->id) }}"
                              method="POST"
                              class="inline">

                            @csrf
                            @method('DELETE')


                            <button type="submit"
                                    onclick="return confirm('Delete this bank account?')"
                                    class="text-red-600">

                                Delete

                            </button>

                        </form>


                    </td>


                </tr>


            @empty


                <tr>

                    <td colspan="7"
                        class="p-8 text-center text-gray-500">

                        No bank accounts found.

                        <br>

                        Add your first bank account.

                    </td>

                </tr>


            @endforelse


            </tbody>

        </table>

    </div>


</div>


@endsection