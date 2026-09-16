@extends('layouts.app')

@section('title', 'Make Payment')

@section('content')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="grid md:grid-cols-2 gap-8">

```
        {{-- Customer Details --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">Customer Details</h2>

            <div class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Customer Name:</span>
                    <span class="font-semibold text-right">
                        {{ $customer->name }}
                    </span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Customer Code:</span>
                    <span class="font-semibold text-right">
                        {{ $customerCode }}
                    </span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Phone:</span>
                    <span class="text-right">
                        {{ $customer->phone ?? '-' }}
                    </span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Email:</span>
                    <span class="text-right">
                        {{ $customer->email ?? '-' }}
                    </span>
                </div>

                <div class="border-t pt-4 mt-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-800 font-bold">Amount Due:</span>
                        <span class="text-xl font-bold
                            {{ $customer->balance_type === 'Receivable'
                                ? 'text-red-600'
                                : 'text-green-600' }}">
                            ৳ {{ number_format((float) $customer->totalDue(), 2) }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">
                        Balance shown from the customer's current account record.
                    </p>
                </div>
            </div>
        </div>

        {{-- Payment Form --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">Make Payment</h2>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <p class="font-semibold mb-2">Please correct the following:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                id="paymentForm"
                action="{{ route('customer-payment.initiate', ['customerCode' => $customerCode]) }}"
                method="POST"
                class="space-y-5"
            >
                @csrf

                {{-- Amount --}}
                <div>
                    <label
                        for="amount"
                        class="block text-gray-700 font-semibold mb-2"
                    >
                        Payment Amount (৳)
                    </label>

                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        step="0.01"
                        min="0.01"
                        value="{{ old('amount') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter payment amount"
                        required
                        inputmode="decimal"
                    >

                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Payment Method --}}
                <div>
                    <label
                        for="paymentMethod"
                        class="block text-gray-700 font-semibold mb-2"
                    >
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        id="paymentMethod"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">-- Select Payment Method --</option>

                        <option
                            value="sslcommerz"
                            {{ old('payment_method') === 'sslcommerz' ? 'selected' : '' }}
                        >
                            SSLCommerz (Card / Mobile Banking)
                        </option>

                        <option
                            value="bkash"
                            {{ old('payment_method') === 'bkash' ? 'selected' : '' }}
                        >
                            bKash
                        </option>

                        <option
                            value="bank_transfer"
                            {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}
                        >
                            Bank Transfer
                        </option>
                    </select>

                    @error('payment_method')
                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-bold py-3 rounded-lg transition"
                >
                    Pay Now
                </button>
            </form>

            {{-- Payment Information --}}
            <div class="mt-8 pt-6 border-t">
                <h3 class="font-semibold text-gray-700 mb-3">
                    Payment Methods
                </h3>

                <div class="text-sm text-gray-600 space-y-2">
                    <p>
                        <strong>SSLCommerz:</strong>
                        Secure payment through supported cards and mobile banking services.
                    </p>

                    <p>
                        <strong>bKash:</strong>
                        Submit your bKash transaction details after making the payment.
                    </p>

                    <p>
                        <strong>Bank Transfer:</strong>
                        Payment is recorded as pending until the transfer is confirmed.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
```

</div>

<script>
    document.getElementById('paymentForm').addEventListener('submit', function () {
        const submitBtn = document.getElementById('submitBtn');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    });
</script>

@endsection
