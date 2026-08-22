@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800">Salaries</h1>
        <a href="{{ route('salaries.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
            + New Salary Record
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded-md bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Employee</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Month/Year</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Basic</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Allowances</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Deductions</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Net Salary</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($salaries as $salary)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $salary->employee->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $salary->month }}/{{ $salary->year }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($salary->basic_salary, 2) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($salary->allowances, 2) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($salary->deductions, 2) }}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ number_format($salary->net_salary, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($salary->status === 'paid')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Paid</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('salaries.show', $salary) }}" class="text-blue-600 hover:underline">View</a>

                            @if ($salary->status === 'pending')
                                <form action="{{ route('salaries.mark-paid', $salary) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline">Mark Paid</button>
                                </form>
                            @endif

                            <form action="{{ route('salaries.destroy', $salary) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this salary record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-400">No salary records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $salaries->links() }}
    </div>
</div>
@endsection