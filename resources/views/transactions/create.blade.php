
<@extends('layouts.app')

@section('title','Add Transaction')

@section('page-title','Add Transaction')

@section('page-subtitle','Create a new transaction')

@section('header')
<div class="flex justify-between items-center">
    <h2 class="font-semibold text-2xl text-gray-800">
        Add Transaction
    </h2>

    <a href="{{ route('transactions.index') }}"
       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
        Back
    </a>
</div>
@endsection


@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Add Transaction</h4>

        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company</label>
                        <input
                            type="text"
                            class="form-control"
                            value="{{ $company->company_name }}"
                            readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Transaction Date</label>
                        <input
                            type="date"
                            name="transaction_date"
                            class="form-control"
                            value="{{ old('transaction_date', date('Y-m-d')) }}"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Transaction Type</label>

                        <select
                            name="transaction_type"
                            class="form-select"
                            required>

                            <option value="">Select</option>

                            <option value="Income">Income</option>

                            <option value="Expense">Expense</option>

                            <option value="Journal">Journal</option>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount</label>

                        <input
                            type="number"
                            step="0.01"
                            name="amount"
                            class="form-control"
                            value="{{ old('amount') }}"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Debit Account</label>

                        <select
                            name="debit_account_id"
                            class="form-select"
                            required>

                            <option value="">Select Account</option>

                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }}
                                    -
                                    {{ $account->account_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Credit Account</label>

                        <select
                            name="credit_account_id"
                            class="form-select"
                            required>

                            <option value="">Select Account</option>

                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }}
                                    -
                                    {{ $account->account_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>

                        <textarea
                            name="description"
                            rows="4"
                            class="form-control">{{ old('description') }}</textarea>
                    </div>

                </div>

                <button class="btn btn-primary">
                    Save Transaction
                </button>

            </form>

        </div>
    </div>

</div>
@endsection

