@extends('layouts.app')

@section('title', 'Purchase Order')
@section('page-title', 'Purchase Order Details')
@section('page-subtitle', $purchaseOrder->po_number)

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

```
{{-- Success Message --}}
@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg print:hidden">
        {{ session('success') }}
    </div>
@endif

{{-- Error Message --}}
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg print:hidden">
        {{ session('error') }}
    </div>
@endif

{{-- Purchase Order Document --}}
<div
    id="purchase-order-document"
    class="bg-white rounded-xl shadow-sm p-6"
>

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6">

        <div>

            <h2 class="text-2xl font-bold text-slate-800">
                {{ $purchaseOrder->po_number }}
            </h2>

            <p class="text-slate-500 text-sm mt-1">
                Order Date:
                {{ $purchaseOrder->order_date->format('d M Y') }}
            </p>

            @if($purchaseOrder->expected_date)
                <p class="text-slate-500 text-sm mt-1">
                    Expected Delivery:
                    {{ $purchaseOrder->expected_date->format('d M Y') }}
                </p>
            @endif

        </div>

        @php
            $colors = [
                'Draft'     => 'bg-gray-100 text-gray-700',
                'Confirmed' => 'bg-blue-100 text-blue-700',
                'Received'  => 'bg-green-100 text-green-700',
                'Cancelled' => 'bg-red-100 text-red-700',
            ];
        @endphp

        <span
            class="px-3 py-1 rounded-full text-sm font-medium {{ $colors[$purchaseOrder->status] ?? 'bg-gray-100 text-gray-700' }}"
        >
            {{ $purchaseOrder->status }}
        </span>

    </div>

    {{-- Vendor Information --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 p-4 bg-slate-50 rounded-lg">

        <div>

            <p class="text-xs font-medium text-slate-500 uppercase mb-1">
                Vendor
            </p>

            <p class="font-semibold text-slate-800">
                {{ $purchaseOrder->vendor->name }}
            </p>

            @if($purchaseOrder->vendor->phone)
                <p class="text-sm text-slate-600 mt-1">
                    {{ $purchaseOrder->vendor->phone }}
                </p>
            @endif

            @if($purchaseOrder->vendor->email)
                <p class="text-sm text-slate-600">
                    {{ $purchaseOrder->vendor->email }}
                </p>
            @endif

        </div>

        <div>

            <p class="text-xs font-medium text-slate-500 uppercase mb-1">
                Delivery
            </p>

            <p class="text-sm text-slate-700">
                Expected:
                {{ $purchaseOrder->expected_date?->format('d M Y') ?? 'N/A' }}
            </p>

        </div>

    </div>

    {{-- Items --}}
    <div class="overflow-x-auto mb-6">

        <table class="w-full text-sm">

            <thead class="bg-slate-50 border-b">

                <tr>

                    <th class="text-left px-4 py-3 font-medium">
                        Item
                    </th>

                    <th class="text-left px-4 py-3 font-medium">
                        Description
                    </th>

                    <th class="text-right px-4 py-3 font-medium">
                        Qty
                    </th>

                    <th class="text-left px-4 py-3 font-medium">
                        Unit
                    </th>

                    <th class="text-right px-4 py-3 font-medium">
                        Price
                    </th>

                    <th class="text-right px-4 py-3 font-medium">
                        Total
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y">

                @foreach($purchaseOrder->items as $item)

                    <tr>

                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $item->item_name }}
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            {{ $item->description ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            {{ $item->quantity }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->unit ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            ৳{{ number_format((float) $item->unit_price, 2) }}
                        </td>

                        <td class="px-4 py-3 text-right font-medium">
                            ৳{{ number_format((float) $item->total, 2) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    {{-- Totals --}}
    <div class="flex justify-end mb-6">

        <div class="w-80 space-y-2 text-sm">

            {{-- Subtotal --}}
            <div class="flex justify-between">

                <span class="text-slate-600">
                    Subtotal:
                </span>

                <span class="font-medium">
                    ৳{{ number_format((float) $purchaseOrder->subtotal, 2) }}
                </span>

            </div>

            {{-- Tax --}}
            <div class="flex justify-between">

                <span class="text-slate-600">
                    Tax ({{ number_format((float) $taxPercentage, 2) }}%):
                </span>

                <span>
                    ৳{{ number_format((float) $purchaseOrder->tax, 2) }}
                </span>

            </div>

            {{-- Discount --}}
            <div class="flex justify-between">

                <span class="text-slate-600">
                    Discount ({{ number_format((float) $discountPercentage, 2) }}%):
                </span>

                <span>
                    -৳{{ number_format((float) $purchaseOrder->discount, 2) }}
                </span>

            </div>

            {{-- Total --}}
            <div class="flex justify-between font-bold text-base border-t pt-2">

                <span>
                    Total:
                </span>

                <span>
                    ৳{{ number_format((float) $purchaseOrder->total, 2) }}
                </span>

            </div>

        </div>

    </div>

    {{-- Amount in Words --}}
    <div class="border-t pt-4 mb-6">

        <p class="text-sm leading-6">

            <span class="font-medium text-slate-600">
                Amount in Words:
            </span>

            <span class="font-semibold text-slate-800">
                {{ $amountInWords }}
            </span>

        </p>

    </div>

    {{-- Notes --}}
    @if($purchaseOrder->notes)

        <div class="p-4 bg-slate-50 rounded-lg mb-8">

            <p class="text-xs font-medium text-slate-500 uppercase mb-1">
                Notes
            </p>

            <p class="text-sm text-slate-700 whitespace-pre-line">
                {{ $purchaseOrder->notes }}
            </p>

        </div>

    @endif

    {{-- Signature --}}
    <div class="grid grid-cols-2 gap-16 mt-12 pt-6">

        <div class="text-center">

            <div class="border-t border-slate-400 pt-2 text-sm text-slate-600">
                Authorized By
            </div>

        </div>

        <div class="text-center">

            <div class="border-t border-slate-400 pt-2 text-sm text-slate-600">
                Vendor
            </div>

        </div>

    </div>

</div>

{{-- Print / PDF Actions --}}
<div class="flex justify-end gap-2 print:hidden">

    <button
        type="button"
        onclick="window.print()"
        class="bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800"
    >
        🖨 Print
    </button>

    <a
        href="{{ route('purchase-orders.pdf', $purchaseOrder) }}"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
    >
        📄 PDF
    </a>

</div>

{{-- Status Update --}}
@if(
    $purchaseOrder->status !== 'Cancelled' &&
    $purchaseOrder->status !== 'Received'
)

    <div class="bg-white rounded-xl shadow-sm p-6 print:hidden">

        <div class="border-t pt-4">

            <p class="text-sm font-medium text-slate-700 mb-2">
                Update Status:
            </p>

            <div class="flex flex-wrap gap-2">

                {{-- Draft -> Confirmed --}}
                @if($purchaseOrder->status === 'Draft')

                    <form
                        method="POST"
                        action="{{ route('purchase-orders.status', $purchaseOrder) }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="status"
                            value="Confirmed"
                        >

                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
                        >
                            ✓ Confirm Order
                        </button>

                    </form>

                @endif

                {{-- Confirmed -> Received --}}
                @if($purchaseOrder->status === 'Confirmed')

                    <form
                        method="POST"
                        action="{{ route('purchase-orders.status', $purchaseOrder) }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="status"
                            value="Received"
                        >

                        <button
                            type="submit"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700"
                        >
                            ✓ Mark as Received
                        </button>

                    </form>

                @endif

                {{-- Cancel --}}
                <form
                    method="POST"
                    action="{{ route('purchase-orders.status', $purchaseOrder) }}"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="status"
                        value="Cancelled"
                    >

                    <button
                        type="submit"
                        class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm hover:bg-red-200"
                        onclick="return confirm('Cancel this order?')"
                    >
                        Cancel Order
                    </button>

                </form>

            </div>

        </div>

    </div>

@endif

{{-- Back --}}
<div class="print:hidden">

    <a
        href="{{ route('purchase-orders.index') }}"
        class="text-sm text-blue-600 hover:underline"
    >
        ← Back to Purchase Orders
    </a>

</div>
```

</div>

{{-- Print CSS --}}
@push('styles')

<style>

@media print {

    body {
        background: #ffffff !important;
    }

    body * {
        visibility: hidden;
    }

    #purchase-order-document,
    #purchase-order-document * {
        visibility: visible;
    }

    #purchase-order-document {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 20px;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    @page {
        size: A4;
        margin: 12mm;
    }

}

</style>

@endpush

@endsection
