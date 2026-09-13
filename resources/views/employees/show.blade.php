@extends('layouts.app')

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">

    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            {{ $employee->name }}
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Employee Details
        </p>
    </div>

    <div class="flex items-center gap-2">

        <a href="{{ route('employees.edit', $employee) }}"
           class="inline-flex items-center justify-center px-4 py-2
                  text-sm font-medium text-white bg-amber-600
                  hover:bg-amber-700 rounded-lg shadow-sm transition">
            Edit
        </a>

        <a href="{{ route('employees.index') }}"
           class="inline-flex items-center justify-center px-4 py-2
                  text-sm font-medium text-gray-700 bg-white
                  border border-gray-300 rounded-lg shadow-sm
                  hover:bg-gray-50 transition">
            ← Back to Employees
        </a>

    </div>

</div>

{{-- Employee Information --}}
<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden mb-6">

    <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-800">
            Employee Information
        </h2>
    </div>

    <div class="p-5">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Photo --}}
            <div class="lg:col-span-1">

                <div class="flex flex-col items-center justify-center
                            min-h-48 rounded-xl border border-gray-200
                            bg-gray-50 p-4">

                    @if ($employee->photo_path)
                        <div class="text-center">

                            <div class="w-32 h-32 mx-auto rounded-xl
                                        overflow-hidden border border-gray-200
                                        bg-white">

                                <div class="w-full h-full flex items-center
                                            justify-center text-gray-400 text-sm">
                                    Photo stored
                                </div>

                            </div>

                            <p class="mt-3 text-xs text-gray-500">
                                Employee photo is securely stored.
                            </p>

                        </div>
                    @else
                        <div class="w-32 h-32 rounded-xl bg-gray-200
                                    flex items-center justify-center
                                    text-gray-500 text-sm">
                            No Photo
                        </div>

                        <p class="mt-3 text-xs text-gray-500">
                            No employee photo uploaded.
                        </p>
                    @endif

                </div>

            </div>

            {{-- Details --}}
            <div class="lg:col-span-2">

                <div class="overflow-hidden rounded-xl border border-gray-200">

                    <table class="min-w-full text-sm">

                        <tbody class="divide-y divide-gray-100">

                            <tr>
                                <th class="px-4 py-3 text-left w-1/3
                                           bg-gray-50 font-medium text-gray-600">
                                    Name
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->name }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Designation
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->designation ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Department
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->department ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Primary Mobile
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->phone ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Alternate Mobile
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->alternate_phone ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    NID Number
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->nid_number ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Joining Date
                                </th>

                                <td class="px-4 py-3 text-gray-800">
                                    {{ $employee->joining_date?->format('d M, Y') ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Basic Salary
                                </th>

                                <td class="px-4 py-3 text-gray-800 font-medium">
                                    {{ number_format($employee->basic_salary, 2) }}
                                </td>
                            </tr>

                            <tr>
                                <th class="px-4 py-3 text-left
                                           bg-gray-50 font-medium text-gray-600">
                                    Status
                                </th>

                                <td class="px-4 py-3">

                                    @if ($employee->is_active)

                                        <span class="inline-flex items-center
                                                     px-2.5 py-1 text-xs
                                                     font-medium rounded-full
                                                     bg-green-100 text-green-700">
                                            Active
                                        </span>

                                    @else

                                        <span class="inline-flex items-center
                                                     px-2.5 py-1 text-xs
                                                     font-medium rounded-full
                                                     bg-gray-100 text-gray-500">
                                            Inactive
                                        </span>

                                    @endif

                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

{{-- Salary History --}}
<div class="mb-6">

    <div class="flex items-center justify-between mb-3">

        <h2 class="text-lg font-semibold text-gray-800">
            Salary History
        </h2>

    </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow-sm
                ring-1 ring-gray-200">

        <table class="min-w-full divide-y divide-gray-200 text-sm">

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                        Month/Year
                    </th>

                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                        Net Salary
                    </th>

                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                        Status
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y divide-gray-100">

                @forelse ($employee->salaries as $salary)

                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3 text-gray-800">
                            {{ $salary->month }}/{{ $salary->year }}
                        </td>

                        <td class="px-4 py-3 text-gray-800">
                            {{ number_format($salary->net_salary, 2) }}
                        </td>

                        <td class="px-4 py-3">

                            @if ($salary->status === 'paid')

                                <span class="inline-flex items-center
                                             px-2.5 py-1 text-xs
                                             font-medium rounded-full
                                             bg-green-100 text-green-700">
                                    Paid
                                </span>

                            @else

                                <span class="inline-flex items-center
                                             px-2.5 py-1 text-xs
                                             font-medium rounded-full
                                             bg-yellow-100 text-yellow-700">
                                    Pending
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3"
                            class="px-4 py-6 text-center text-gray-400">
                            No salary records yet.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

{{-- Danger Zone --}}
<div class="bg-white rounded-xl shadow-sm ring-1 ring-red-200 overflow-hidden">

    <div class="px-5 py-4 bg-red-50 border-b border-red-100">

        <h2 class="text-base font-semibold text-red-800">
            Employee Actions
        </h2>

    </div>

    <div class="px-5 py-4 flex flex-col sm:flex-row
                sm:items-center sm:justify-between gap-4">

        <p class="text-sm text-gray-500">
            Deleting an employee is permanent.
        </p>

        <form action="{{ route('employees.destroy', $employee) }}"
              method="POST"
              onsubmit="return confirm('Delete this employee? This action cannot be undone.');">

            @csrf
            @method('DELETE')

            <button type="submit"
                    class="inline-flex items-center justify-center
                           px-4 py-2 text-sm font-medium
                           text-red-700 bg-red-50
                           border border-red-200 rounded-lg
                           hover:bg-red-100 transition">
                Delete Employee
            </button>

        </form>

    </div>

</div>

</div>
@endsection
