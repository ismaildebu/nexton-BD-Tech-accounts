@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Manage your company invoices')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Invoices</h2>
        <a href="{{ route('invoices.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + New Invoice
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Invoice #</th>
                    <th class="text-left px-4 py-3 font-medium">Customer</th>
                    <th class="text-left px-4 py-3 font-medium">Invoice Date</th>
                    <th class="text-left px-4 py-3 font-medium">Due Date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="font-medium text-blue-600 hover:underline">
                            {{ $invoice->invoice_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3">{{ $invoice->customer->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ optional($invoice->invoice_date)->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ optional($invoice->due_date)->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">৳{{ number_format($invoice->total_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($invoice->status === 'paid')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                        @elseif($invoice->is_overdue)
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Unpaid</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('invoices.show', $invoice) }}"
                               class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">View</a>
                            <a href="{{ route('invoices.edit', $invoice) }}"
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600">Edit</a>
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                                  onsubmit="return confirm('Delete this invoice?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">No invoices found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
</div>
@endsection