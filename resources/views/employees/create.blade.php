@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">New Employee</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employees.store') }}" method="POST"
          class="bg-white p-6 rounded-lg shadow ring-1 ring-gray-200 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" required value="{{ old('name') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
            <input type="text" name="designation" value="{{ old('designation') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <input type="text" name="department" value="{{ old('department') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label>
            <input type="date" name="joining_date" value="{{ old('joining_date') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary</label>
            <input type="number" step="0.01" min="0" name="basic_salary" required value="{{ old('basic_salary') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('employees.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
                Save Employee
            </button>
        </div>
    </form>
</div>
@endsection