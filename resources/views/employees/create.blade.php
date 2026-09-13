@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

            <div>
                <h1 class="text-2xl font-semibold text-gray-800">
                    Add Employees
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Add one or multiple employees at the same time.
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

    <form action="{{ route('employees.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="employee-form">

        @csrf

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">

            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">

                <div class="flex flex-col sm:flex-row sm:items-center
                            sm:justify-between gap-3">

                    <div>
                        <h2 class="text-base font-semibold text-gray-800">
                            Employee Information
                        </h2>

                        <p class="text-xs text-gray-500 mt-1">
                            Required fields are marked with *
                        </p>
                    </div>

                    <button type="button"
                            id="add-employee"
                            class="inline-flex items-center justify-center gap-2
                                   px-4 py-2 bg-blue-600 hover:bg-blue-700
                                   text-white text-sm font-medium rounded-lg
                                   shadow-sm transition">

                        <span class="text-lg leading-none">+</span>

                        Add Employee

                    </button>

                </div>

            </div>

            <div id="employee-rows" class="p-5 space-y-5">

                @php
                    $oldEmployees = old('employees', [
                        [
                            'name' => '',
                            'designation' => '',
                            'department' => '',
                            'phone' => '',
                            'alternate_phone' => '',
                            'nid_number' => '',
                            'joining_date' => '',
                            'basic_salary' => '',
                        ],
                    ]);
                @endphp

                @foreach ($oldEmployees as $index => $employee)

                    <div class="employee-row border border-gray-200
                                rounded-xl bg-gray-50 overflow-hidden"
                         data-index="{{ $index }}">

                        <div class="px-4 py-3 bg-gray-100 border-b
                                    border-gray-200 flex items-center
                                    justify-between">

                            <div class="flex items-center gap-2">

                                <span class="flex items-center justify-center
                                             w-7 h-7 rounded-full bg-blue-600
                                             text-white text-xs font-semibold
                                             row-number">
                                    {{ $index + 1 }}
                                </span>

                                <span class="text-sm font-semibold text-gray-700">
                                    Employee
                                </span>

                            </div>

                            <button type="button"
                                    class="remove-employee text-sm font-medium
                                           text-red-600 hover:text-red-800
                                           hover:bg-red-50 px-3 py-1.5
                                           rounded-lg transition">
                                Remove
                            </button>

                        </div>

                        <div class="p-4">

                            <div class="grid grid-cols-1 md:grid-cols-2
                                        lg:grid-cols-4 gap-4">

                                {{-- Name --}}
                                <div class="lg:col-span-2">

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Name <span class="text-red-500">*</span>
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][name]"
                                           value="{{ $employee['name'] ?? '' }}"
                                           required
                                           placeholder="Enter employee name"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Designation --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Designation
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][designation]"
                                           value="{{ $employee['designation'] ?? '' }}"
                                           placeholder="e.g. Accountant"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Department --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Department
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][department]"
                                           value="{{ $employee['department'] ?? '' }}"
                                           placeholder="e.g. Accounts"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Primary Mobile --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Primary Mobile
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][phone]"
                                           value="{{ $employee['phone'] ?? '' }}"
                                           maxlength="20"
                                           placeholder="01XXXXXXXXX"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Alternate Mobile --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Alternate Mobile
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][alternate_phone]"
                                           value="{{ $employee['alternate_phone'] ?? '' }}"
                                           maxlength="20"
                                           placeholder="01XXXXXXXXX"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- NID --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        NID Number
                                    </label>

                                    <input type="text"
                                           name="employees[{{ $index }}][nid_number]"
                                           value="{{ $employee['nid_number'] ?? '' }}"
                                           maxlength="50"
                                           placeholder="Enter NID number"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Joining Date --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Joining Date
                                    </label>

                                    <input type="date"
                                           name="employees[{{ $index }}][joining_date]"
                                           value="{{ $employee['joining_date'] ?? '' }}"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Basic Salary --}}
                                <div>

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Basic Salary
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input type="number"
                                           name="employees[{{ $index }}][basic_salary]"
                                           value="{{ $employee['basic_salary'] ?? '' }}"
                                           required
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           class="w-full rounded-lg border-gray-300
                                                  shadow-sm focus:border-blue-500
                                                  focus:ring-blue-500 text-sm">

                                </div>

                                {{-- Photo --}}
                                <div class="lg:col-span-2">

                                    <label class="block text-sm font-medium
                                                  text-gray-700 mb-1">
                                        Employee Photo
                                    </label>

                                    <input type="file"
                                           name="employees[{{ $index }}][photo]"
                                           accept="image/jpeg,image/png,image/webp"
                                           class="block w-full text-sm text-gray-600
                                                  file:mr-3 file:py-2 file:px-3
                                                  file:rounded-lg file:border-0
                                                  file:text-xs file:font-medium
                                                  file:bg-blue-50
                                                  file:text-blue-700
                                                  hover:file:bg-blue-100">

                                    <p class="mt-1 text-xs text-gray-500">
                                        JPG, JPEG, PNG or WEBP. Maximum 2 MB.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

            <div class="px-5 py-4 bg-gray-50 border-t border-gray-200">

                <div class="flex flex-col sm:flex-row
                            sm:items-center sm:justify-between gap-4">

                    <div class="text-sm text-gray-500">

                        Total Employees:

                        <span id="employee-count"
                              class="font-semibold text-gray-800">
                            {{ count($oldEmployees) }}
                        </span>

                    </div>

                    <div class="flex items-center justify-end gap-3">

                        <a href="{{ route('employees.index') }}"
                           class="px-4 py-2 text-sm font-medium
                                  text-gray-600 hover:text-gray-800">
                            Cancel
                        </a>

                        <button type="submit"
                                id="save-employees"
                                class="inline-flex items-center justify-center
                                       px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                                       text-white text-sm font-medium
                                       rounded-lg shadow-sm transition">
                            Save All Employees
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('employee-rows');
    const addButton = document.getElementById('add-employee');
    const countElement = document.getElementById('employee-count');
    const form = document.getElementById('employee-form');
    const saveButton = document.getElementById('save-employees');

    let employeeIndex = {{ count($oldEmployees) }};

    function updateRows() {
        const rows = rowsContainer.querySelectorAll('.employee-row');

        rows.forEach((row, index) => {
            const number = row.querySelector('.row-number');

            if (number) {
                number.textContent = index + 1;
            }
        });

        countElement.textContent = rows.length;
    }

    function createEmployeeRow() {
        const row = document.createElement('div');

        row.className =
            'employee-row border border-gray-200 rounded-xl ' +
            'bg-gray-50 overflow-hidden';

        row.dataset.index = employeeIndex;

        row.innerHTML = `
            <div class="px-4 py-3 bg-gray-100 border-b border-gray-200
                        flex items-center justify-between">

                <div class="flex items-center gap-2">

                    <span class="flex items-center justify-center
                                 w-7 h-7 rounded-full bg-blue-600
                                 text-white text-xs font-semibold row-number">
                        1
                    </span>

                    <span class="text-sm font-semibold text-gray-700">
                        Employee
                    </span>

                </div>

                <button type="button"
                        class="remove-employee text-sm font-medium
                               text-red-600 hover:text-red-800
                               hover:bg-red-50 px-3 py-1.5
                               rounded-lg transition">
                    Remove
                </button>

            </div>

            <div class="p-4">

                <div class="grid grid-cols-1 md:grid-cols-2
                            lg:grid-cols-4 gap-4">

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][name]"
                               required
                               placeholder="Enter employee name"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Designation
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][designation]"
                               placeholder="e.g. Accountant"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Department
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][department]"
                               placeholder="e.g. Accounts"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Primary Mobile
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][phone]"
                               maxlength="20"
                               placeholder="01XXXXXXXXX"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Alternate Mobile
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][alternate_phone]"
                               maxlength="20"
                               placeholder="01XXXXXXXXX"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            NID Number
                        </label>

                        <input type="text"
                               name="employees[${employeeIndex}][nid_number]"
                               maxlength="50"
                               placeholder="Enter NID number"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Joining Date
                        </label>

                        <input type="date"
                               name="employees[${employeeIndex}][joining_date]"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Basic Salary
                            <span class="text-red-500">*</span>
                        </label>

                        <input type="number"
                               name="employees[${employeeIndex}][basic_salary]"
                               required
                               min="0"
                               step="0.01"
                               placeholder="0.00"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">
                    </div>

                    <div class="lg:col-span-2">

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">
                            Employee Photo
                        </label>

                        <input type="file"
                               name="employees[${employeeIndex}][photo]"
                               accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-sm text-gray-600
                                      file:mr-3 file:py-2 file:px-3
                                      file:rounded-lg file:border-0
                                      file:text-xs file:font-medium
                                      file:bg-blue-50
                                      file:text-blue-700
                                      hover:file:bg-blue-100">

                        <p class="mt-1 text-xs text-gray-500">
                            JPG, JPEG, PNG or WEBP. Maximum 2 MB.
                        </p>

                    </div>

                </div>

            </div>
        `;

        rowsContainer.appendChild(row);

        employeeIndex++;

        updateRows();

        const nameInput = row.querySelector(
            'input[name*="[name]"]'
        );

        if (nameInput) {
            nameInput.focus();
        }
    }

    addButton.addEventListener('click', function () {
        createEmployeeRow();
    });

    rowsContainer.addEventListener('click', function (event) {
        const removeButton =
            event.target.closest('.remove-employee');

        if (!removeButton) {
            return;
        }

        const rows =
            rowsContainer.querySelectorAll('.employee-row');

        if (rows.length === 1) {
            return;
        }

        removeButton.closest('.employee-row').remove();

        updateRows();
    });

    form.addEventListener('submit', function () {
        saveButton.disabled = true;
        saveButton.classList.add(
            'opacity-60',
            'cursor-not-allowed'
        );

        saveButton.textContent = 'Saving Employees...';
    });

    updateRows();
});
</script>
@endsection