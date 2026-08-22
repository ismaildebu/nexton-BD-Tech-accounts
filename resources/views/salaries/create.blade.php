@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">New Salary Record</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('salaries.store') }}" method="POST"
          class="bg-white p-6 rounded-lg shadow ring-1 ring-gray-200 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select name="employee_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Select Employee --</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                        {{ $employee->name }} ({{ number_format($employee->basic_salary, 2) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <select name="month" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected(old('month', date('n')) == $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <input type="number" name="year" required value="{{ old('year', date('Y')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Allowances</label>
            <input type="number" step="0.01" min="0" name="allowances" value="{{ old('allowances', 0) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deductions</label>
            <input type="number" step="0.01" min="0" name="deductions" value="{{ old('deductions', 0) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('salaries.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
                Save Salary Record
            </button>
        </div>
    </form>
</div>
@endsection