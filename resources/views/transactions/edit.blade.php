<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800">
            Edit Transaction
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto">

            <div class="bg-white shadow rounded-lg p-6">

                <form action="{{ route('transactions.update',$transaction->id) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label>Company</label>

                        <input
                            type="text"
                            class="w-full border rounded p-2 bg-gray-100"
                            value="{{ $company->company_name }}"
                            readonly>

                    </div>

                    <div class="mb-4">
                        <label>Account</label>

                        <div class="mb-4">
    <label>Debit Account</label>

    <select name="debit_account_id" class="w-full border rounded p-2">

        @foreach($accounts as $account)

            <option value="{{ $account->id }}"
                @selected($transaction->debit_account_id == $account->id)>

                {{ $account->account_code }} - {{ $account->account_name }}

            </option>

        @endforeach

    </select>
</div>

<div class="mb-4">
    <label>Credit Account</label>

    <select name="credit_account_id" class="w-full border rounded p-2">

        @foreach($accounts as $account)

            <option value="{{ $account->id }}"
                @selected($transaction->credit_account_id == $account->id)>

                {{ $account->account_code }} - {{ $account->account_name }}

            </option>

        @endforeach

    </select>
</div>
                        
                               

                    <div class="mb-4">

                        <label>Date</label>

                        <input
                            type="date"
                            name="transaction_date"
                            value="{{ $transaction->transaction_date }}"
                            class="w-full border rounded p-2">

                    </div>

                    <div class="mb-4">

                        <label>Type</label>

                        <select name="transaction_type"
                                class="w-full border rounded p-2">

                            <option value="Income"
                                @selected($transaction->transaction_type=='Income')>
                                Income
                            </option>

                            <option value="Expense"
                                @selected($transaction->transaction_type=='Expense')>
                                Expense
                            </option>

                            <option value="Journal"
                                @selected($transaction->transaction_type=='Journal')>
                                Journal
                            </option>

                        </select>

                    </div>

                    <div class="mb-4">

                        <label>Amount</label>

                        <input
                            type="number"
                            step="0.01"
                            name="amount"
                            value="{{ $transaction->amount }}"
                            class="w-full border rounded p-2">

                    </div>

                    <div class="mb-4">

                        <label>Description</label>

                        <textarea
                            name="description"
                            class="w-full border rounded p-2"
                            rows="4">{{ $transaction->description }}</textarea>

                    </div>

                    <button
                        class="bg-blue-600 text-white px-5 py-2 rounded">

                        Update Transaction

                    </button>

                    <a href="{{ route('transactions.index') }}"
                       class="bg-gray-500 text-white px-5 py-2 rounded ml-2">

                        Cancel

                    </a>

                </form>

            </div>

        </div>
    </div>

</x-app-layout>