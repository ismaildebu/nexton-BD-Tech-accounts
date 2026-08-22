@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800">Employees</h1>
        <a href="{{ route('employees.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
            + Add Employee
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
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Designation</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Department</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Basic Salary</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employees as $employee)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $employee->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $employee->designation ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $employee->department ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ number_format($employee->basic_salary, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($employee->is_active)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="text-amber-600 hover:underline">Edit</a>
                            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this employee?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>
@endsection