@extends('layouts.app')

@section('title', 'Purchase Bill')
@section('page-title', 'Purchase Bill Details')
@section('page-subtitle', $purchaseBill->bill_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">

        {{-- Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ $purchaseBill->bill_number }}</h2>
                <p class="text-slate-500 text-sm mt-1">{{ $purchaseBill->bill_date->format('d M Y') }}</p>
                @if($purchaseBill->purchaseOrder)
                <p class="text-xs text-blue-600 mt-1">
                    PO: {{ $purchaseBill->purchaseOrder->po_number }}
                </p>
                @endif
            </div>
            @php
                $colors = [
                    'Unpaid'  => 'bg-red-100 text-red-700',
                    'Partial' => 'bg-yellow-100 text-yellow-700',
                    'Paid'    => 'bg-green-100 text-green-700',
                ];
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $colors[$purchaseBill->status] ?? '' }}">
                {{ $purchaseBill->status }}
            </span>
        </div>

        {{-- Vendor & Due Date --}}
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-slate-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Vendor</p>
                <p class="font-semibold">{{ $purchaseBill->vendor->name }}</p>
                <p class="text-sm text-slate-600">{{ $purchaseBill->vendor->phone }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase mb-1">Payment Info</p>
                <p class="text-sm">Due: {{ $purchaseBill->due_date?->format('d M Y') ?? 'N/A' }}</p>
                <p class="text-sm text-green-600">Paid: ৳{{ number_format($purchaseBill->paid_amount, 2) }}</p>
                <p class="text-sm text-red-600 font-medium">Due: ৳{{ number_format($purchaseBill->due_amount, 2) }}</p>
            </div>
        </div>

        {{-- Items --}}
        <table class="w-full text-sm mb-6">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Item</th>
                    <th class="text-right px-4 py-3 font-medium">Qty</th>
                    <th class="text-left px-4 py-3 font-medium">Unit</th>
                    <th class="text-right px-4 py-3 font-medium">Price</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($purchaseBill->items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $item->item_name }}</td>
                    <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                    <td class="px-4 py-3">{{ $item->unit ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">৳{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="flex justify-end mb-6">
            <div class="w-64 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">Subtotal:</span>
                    <span>৳{{ number_format($purchaseBill->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Tax:</span>
                    <span>৳{{ number_format($purchaseBill->tax, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Discount:</span>
                    <span>-৳{{ number_format($purchaseBill->discount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base border-t pt-2">
                    <span>Total:</span>
                    <span>৳{{ number_format($purchaseBill->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        @if($purchaseBill->payments->count() > 0)
        <div class="mb-6">
            <h3 class="font-semibold text-slate-700 mb-3">Payment History</h3>
            <table class="w-full text-sm border rounded-lg overflow-hidden">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">Date</th>
                        <th class="text-left px-4 py-2 font-medium">Method</th>
                        <th class="text-left px-4 py-2 font-medium">Reference</th>
                        <th class="text-right px-4 py-2 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($purchaseBill->payments as $payment)
                    <tr>
                        <td class="px-4 py-2">{{ $payment->payment_date->format('d M Y') }}</td>
                        <td class="px-4 py-2">{{ $payment->payment_method }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $payment->reference ?? '-' }}</td>
                        <td class="px-4 py-2 text-right font-medium text-green-600">
                            ৳{{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Add Payment --}}
        @if($purchaseBill->status !== 'Paid')
        <div class="border-t pt-4" x-data="{ showPayment: false }">
            <button @click="showPayment = !showPayment"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 mb-4">
                + Add Payment
            </button>

            <div x-show="showPayment" x-transition>
                <form method="POST" action="{{ route('purchase-bills.payment', $purchaseBill) }}"
                      class="bg-slate-50 p-4 rounded-lg grid grid-cols-2 md:grid-cols-4 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Amount * (Max: ৳{{ number_format($purchaseBill->due_amount, 2) }})
                        </label>
                        <input type="number" name="amount" step="0.01"
                               max="{{ $purchaseBill->due_amount }}" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Method *</label>
                        <select name="payment_method" required
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <option>Cash</option>
                            <option>Bank Transfer</option>
                            <option>Cheque</option>
                            <option>Mobile Banking</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Reference</label>
                        <input type="text" name="reference"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <button type="submit"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                            Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('purchase-bills.index') }}"
               class="text-sm text-blue-600 hover:underline">← Back to Bills</a>
        </div>
    </div>
</div>
@endsection