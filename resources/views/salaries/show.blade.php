@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Salary Detail</h1>
        <a href="{{ route('salaries.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to list</a>
    </div>

    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left w-1/3 bg-gray-50 font-medium text-gray-600">Employee</th>
                    <td class="px-4 py-3 text-gray-800">{{ $salary->employee->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Month/Year</th>
                    <td class="px-4 py-3 text-gray-800">{{ $salary->month }}/{{ $salary->year }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Basic Salary</th>
                    <td class="px-4 py-3 text-gray-800">{{ number_format($salary->basic_salary, 2) }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Allowances</th>
                    <td class="px-4 py-3 text-gray-800">{{ number_format($salary->allowances, 2) }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Deductions</th>
                    <td class="px-4 py-3 text-gray-800">{{ number_format($salary->deductions, 2) }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Net Salary</th>
                    <td class="px-4 py-3 text-gray-800 font-semibold">{{ number_format($salary->net_salary, 2) }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Status</th>
                    <td class="px-4 py-3">
                        @if ($salary->status === 'paid')
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Paid ({{ $salary->paid_date?->format('d M, Y') }})</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection