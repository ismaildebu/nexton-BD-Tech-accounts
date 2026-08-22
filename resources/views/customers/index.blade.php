@extends('layouts.app')
@section('title', 'Customers')
@section('page-title', 'Customers')
@section('page-subtitle', 'Manage your customers')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">All Customers</h2>
        <a href="{{ route('customers.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            + Add Customer
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Name</th>
                    <th class="text-left px-4 py-3 font-medium">Type</th>
                    <th class="text-left px-4 py-3 font-medium">Phone</th>
                    <th class="text-left px-4 py-3 font-medium">Email</th>
                    <th class="text-right px-4 py-3 font-medium">Invoices</th>
                    <th class="text-right px-4 py-3 font-medium">Due</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($customers as $customer)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('customers.show', $customer) }}"
                           class="font-medium text-blue-600 hover:underline">
                            {{ $customer->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $customer->customer_type === 'Business' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $customer->customer_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $customer->email ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $customer->invoices_count }}</td>
                    <td class="px-4 py-3 text-right text-red-600 font-medium">
                        ৳{{ number_format($customer->totalDue(), 2) }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('customers.show', $customer) }}"
                               class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">View</a>
                            <a href="{{ route('customers.edit', $customer) }}"
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600">Edit</a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                  onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection