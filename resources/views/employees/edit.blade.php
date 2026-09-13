```blade
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

            <div>
                <h1 class="text-2xl font-semibold text-gray-800">
                    Edit Employee
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update employee information and personal details.
                </p>
            </div>

            <a href="{{ route('employees.index') }}"
               class="inline-flex items-center justify-center px-4 py-2
                      text-sm font-medium text-gray-700 bg-white
                      border border-gray-300 rounded-lg shadow-sm
                      hover:bg-gray-50 transition">
                ← Back to Employees
            </a>

        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">

            <div class="text-sm font-semibold text-red-800">
                Please correct the following errors:
            </div>

            <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif

    <form action="{{ route('employees.update', $employee) }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">

        @csrf
        @method('PUT')

        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-base font-semibold text-gray-800">
                Employee Information
            </h2>

            <p class="text-xs text-gray-500 mt-1">
                Update the employee details below.
            </p>
        </div>

        <div class="p-5">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Name --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                           name="name"
                           required
                           maxlength="255"
                           value="{{ old('name', $employee->name) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Designation --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Designation
                    </label>

                    <input type="text"
                           name="designation"
                           maxlength="255"
                           value="{{ old('designation', $employee->designation) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Department --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Department
                    </label>

                    <input type="text"
                           name="department"
                           maxlength="255"
                           value="{{ old('department', $employee->department) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Primary Mobile --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Primary Mobile
                    </label>

                    <input type="text"
                           name="phone"
                           maxlength="20"
                           value="{{ old('phone', $employee->phone) }}"
                           placeholder="01XXXXXXXXX"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Alternate Mobile --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Alternate Mobile
                    </label>

                    <input type="text"
                           name="alternate_phone"
                           maxlength="20"
                           value="{{ old('alternate_phone', $employee->alternate_phone) }}"
                           placeholder="01XXXXXXXXX"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- NID Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        NID Number
                    </label>

                    <input type="text"
                           name="nid_number"
                           maxlength="50"
                           value="{{ old('nid_number', $employee->nid_number) }}"
                           placeholder="Enter NID number"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Joining Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Joining Date
                    </label>

                    <input type="date"
                           name="joining_date"
                           value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Basic Salary --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Basic Salary <span class="text-red-500">*</span>
                    </label>

                    <input type="number"
                           name="basic_salary"
                           required
                           min="0"
                           step="0.01"
                           value="{{ old('basic_salary', $employee->basic_salary) }}"
                           placeholder="0.00"
                           class="w-full rounded-lg border-gray-300 shadow-sm
                                  focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                {{-- Employee Photo --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Employee Photo
                    </label>

                    <input type="file"
                           name="photo"
                           accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-600
                                  file:mr-3 file:py-2 file:px-3
                                  file:rounded-lg file:border-0
                                  file:text-xs file:font-medium
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100">

                    <p class="mt-1 text-xs text-gray-500">
                        JPG, JPEG, PNG or WEBP. Maximum 2 MB.
                    </p>

                    @if ($employee->photo_path)
                        <p class="mt-2 text-xs text-gray-500">
                            A photo is already stored for this employee.
                            Upload a new photo to replace it.
                        </p>
                    @endif
                </div>

                {{-- Active Status --}}
                <div class="lg:col-span-2 flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               @checked(old('is_active', $employee->is_active))
                               class="rounded border-gray-300 text-blue-600
                                      focus:ring-blue-500">

                        <span class="text-sm font-medium text-gray-700">
                            Active Employee
                        </span>
                    </label>
                </div>

            </div>

        </div>

        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-end gap-3">

                <a href="{{ route('employees.index') }}"
                   class="px-4 py-2 text-sm font-medium
                          text-gray-600 hover:text-gray-800">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center
                               px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-medium rounded-lg
                               shadow-sm transition">
                    Update Employee
                </button>

            </div>
        </div>

    </form>

</div>
@endsection
```
