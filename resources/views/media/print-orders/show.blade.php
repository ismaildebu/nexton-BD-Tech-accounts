@extends('layouts.app')

@section('page-title', 'Print Order — ' . $order->order_number)
@section('page-subtitle', $order->publication->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $order->order_number }}</h2>
                <p class="text-sm text-slate-500">{{ $order->publication->name }} &middot; Ordered {{ $order->order_date->format('d M Y') }}</p>
            </div>
            <span @class([
                'px-2 py-1 rounded-full text-xs font-medium',
                'bg-amber-50 text-amber-700' => $order->status === 'Draft',
                'bg-blue-50 text-blue-700' => $order->status === 'Ordered',
                'bg-indigo-50 text-indigo-700' => $order->status === 'Printing',
                'bg-purple-50 text-purple-700' => $order->status === 'Printed',
                'bg-green-50 text-green-700' => $order->status === 'Received',
                'bg-red-50 text-red-700' => $order->status === 'Cancelled',
            ])>{{ $order->status }}</span>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div><dt class="text-slate-500">Printing Press</dt><dd class="font-medium">{{ $order->vendor->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Print Date</dt><dd class="font-medium">{{ $order->print_date?->format('d M Y') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Ordered Quantity</dt><dd class="font-medium">{{ number_format($order->ordered_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Printed Quantity</dt><dd class="font-medium">{{ number_format($order->printed_quantity) }}</dd></div>
            <div><dt class="text-slate-500">Received Quantity</dt><dd class="font-medium">{{ number_format($order->received_quantity) }}</dd></div>
            @if($order->printPlan)
            <div><dt class="text-slate-500">From Print Plan</dt><dd class="font-medium"><a href="{{ route('media.print-plans.show', $order->printPlan) }}" class="text-blue-600 hover:underline">{{ $order->printPlan->plan_date->format('d M Y') }}</a></dd></div>
            @endif
            @if($order->notes)
            <div class="col-span-2"><dt class="text-slate-500">Notes</dt><dd class="font-medium">{{ $order->notes }}</dd></div>
            @endif
        </dl>

        <div class="flex flex-wrap gap-3 border-t pt-4">
            @can('print', $order)
            <a href="{{ route('media.print-orders.pdf', $order) }}"
               class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-900">
                Download PDF
            </a>
            @endcan

            @if($order->status === 'Draft')
                @can('update', $order)
                <a href="{{ route('media.print-orders.edit', $order) }}"
                   class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Edit
                </a>
                @endcan
                @can('approve', $order)
                <form method="POST" action="{{ route('media.print-orders.approve', $order) }}">
                    @csrf
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                        Approve Order (mark Ordered)
                    </button>
                </form>
                <form method="POST" action="{{ route('media.print-orders.update-status', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="Cancelled">
                    <button type="submit" class="bg-red-50 text-red-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-100">
                        Cancel Order
                    </button>
                </form>
                @endcan
            @endif

            @can('updateStatus', $order)
                @if($order->status === 'Ordered')
                <form method="POST" action="{{ route('media.print-orders.update-status', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="Printing">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Mark as Printing
                    </button>
                </form>
                <form method="POST" action="{{ route('media.print-orders.update-status', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="Cancelled">
                    <button type="submit" class="bg-red-50 text-red-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-100">
                        Cancel Order
                    </button>
                </form>
                @endif

                @if($order->status === 'Printing')
                <form method="POST" action="{{ route('media.print-orders.update-status', $order) }}" class="flex items-end gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="Printed">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Printed Quantity</label>
                        <input type="number" name="printed_quantity" min="0" required
                               class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm w-32">
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700">
                        Mark as Printed
                    </button>
                </form>
                @endif

                @if($order->status === 'Printed')
                <form method="POST" action="{{ route('media.print-orders.update-status', $order) }}" class="flex items-end gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="Received">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Received Quantity</label>
                        <input type="number" name="received_quantity" min="0" max="{{ $order->printed_quantity }}" required
                               class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm w-32">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                        Mark as Received
                    </button>
                </form>
                @endif
            @endcan
        </div>

        <div class="pt-4">
            <a href="{{ route('media.print-orders.index') }}" class="text-slate-500 text-sm">Back to list</a>
        </div>
    </div>
</div>
@endsection
