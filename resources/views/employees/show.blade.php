@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">{{ $employee->name }}</h1>
        <a href="{{ route('employees.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to list</a>
    </div>

    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden mb-6">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left w-1/3 bg-gray-50 font-medium text-gray-600">Designation</th>
                    <td class="px-4 py-3 text-gray-800">{{ $employee->designation ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Department</th>
                    <td class="px-4 py-3 text-gray-800">{{ $employee->department ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Phone</th>
                    <td class="px-4 py-3 text-gray-800">{{ $employee->phone ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Joining Date</th>
                    <td class="px-4 py-3 text-gray-800">{{ $employee->joining_date?->format('d M, Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Basic Salary</th>
                    <td class="px-4 py-3 text-gray-800">{{ number_format($employee->basic_salary, 2) }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Status</th>
                    <td class="px-4 py-3">
                        @if ($employee->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="text-lg font-semibold text-gray-800 mb-3">Salary History</h2>
    <div class="overflow-x-auto bg-white rounded-lg shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Month/Year</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Net Salary</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employee->salaries as $salary)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $salary->month }}/{{ $salary->year }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ number_format($salary->net_salary, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($salary->status === 'paid')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Paid</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-400">No salary records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection