@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

```
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center
                sm:justify-between gap-4">

        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                New Salary Records
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Add salary records for one or multiple employees.
            </p>
        </div>

        <a href="{{ route('salaries.index') }}"
           class="inline-flex items-center justify-center px-4 py-2
                  text-sm font-medium text-gray-700 bg-white
                  border border-gray-300 rounded-lg shadow-sm
                  hover:bg-gray-50 transition">
            ← Back to Salaries
        </a>

    </div>
</div>

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">

        <div class="text-sm font-semibold text-red-800">
            Please correct the following errors:
        </div>

        <ul class="mt-2 list-disc list-inside text-sm
                   text-red-700 space-y-1">

            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach

        </ul>

    </div>
@endif

<form action="{{ route('salaries.store') }}"
      method="POST"
      id="salary-form">

    @csrf

    <div class="bg-white rounded-xl shadow-sm
                ring-1 ring-gray-200 overflow-hidden">

        <div class="px-5 py-4 bg-gray-50
                    border-b border-gray-200">

            <div class="flex flex-col sm:flex-row
                        sm:items-center sm:justify-between gap-3">

                <div>
                    <h2 class="text-base font-semibold text-gray-800">
                        Salary Information
                    </h2>

                    <p class="text-xs text-gray-500 mt-1">
                        Basic salary is taken automatically from the employee.
                    </p>
                </div>

                <button type="button"
                        id="add-salary"
                        class="inline-flex items-center justify-center
                               gap-2 px-4 py-2 bg-blue-600
                               hover:bg-blue-700 text-white text-sm
                               font-medium rounded-lg shadow-sm transition">

                    <span class="text-lg leading-none">+</span>
                    Add Salary

                </button>

            </div>

        </div>

        <div id="salary-rows" class="p-5 space-y-5">

            @php
                $oldSalaries = old('salaries', [
                    [
                        'employee_id' => '',
                        'month' => date('n'),
                        'year' => date('Y'),
                        'allowances' => '0',
                        'deductions' => '0',
                    ],
                ]);
            @endphp

            @foreach ($oldSalaries as $index => $salary)

                <div class="salary-row border border-gray-200
                            rounded-xl bg-gray-50 overflow-hidden"
                     data-index="{{ $index }}">

                    <div class="px-4 py-3 bg-gray-100
                                border-b border-gray-200
                                flex items-center justify-between">

                        <div class="flex items-center gap-2">

                            <span class="row-number flex items-center
                                         justify-center w-7 h-7
                                         rounded-full bg-blue-600
                                         text-white text-xs
                                         font-semibold">
                                {{ $index + 1 }}
                            </span>

                            <span class="text-sm font-semibold text-gray-700">
                                Salary
                            </span>

                        </div>

                        <button type="button"
                                class="remove-salary text-sm
                                       font-medium text-red-600
                                       hover:text-red-800
                                       hover:bg-red-50 px-3 py-1.5
                                       rounded-lg transition">
                            Remove
                        </button>

                    </div>

                    <div class="p-4">

                        <div class="grid grid-cols-1 md:grid-cols-2
                                    lg:grid-cols-6 gap-4">

                            {{-- Employee --}}
                            <div class="lg:col-span-2">

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Employee
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="salaries[{{ $index }}][employee_id]"
                                    required
                                    class="employee-select w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500 text-sm">

                                    <option value="">
                                        -- Select Employee --
                                    </option>

                                    @foreach ($employees as $employee)

                                        <option
                                            value="{{ $employee->id }}"
                                            data-basic="{{ $employee->basic_salary }}"
                                            @selected(
                                                old(
                                                    "salaries.$index.employee_id"
                                                ) == $employee->id
                                            )>
                                            {{ $employee->name }}
                                            ({{ number_format($employee->basic_salary, 2) }})
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            {{-- Month --}}
                            <div>

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Month
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="salaries[{{ $index }}][month]"
                                    required
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500 text-sm">

                                    @foreach (range(1, 12) as $month)

                                        <option value="{{ $month }}"
                                            @selected(
                                                old(
                                                    "salaries.$index.month",
                                                    date('n')
                                                ) == $month
                                            )>
                                            {{ $month }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            {{-- Year --}}
                            <div>

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Year
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="number"
                                       name="salaries[{{ $index }}][year]"
                                       required
                                       min="2000"
                                       max="2100"
                                       value="{{ old(
                                           "salaries.$index.year",
                                           date('Y')
                                       ) }}"
                                       class="w-full rounded-lg
                                              border-gray-300 shadow-sm
                                              focus:border-blue-500
                                              focus:ring-blue-500 text-sm">

                            </div>

                            {{-- Basic --}}
                            <div>

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Basic Salary
                                </label>

                                <input type="text"
                                       class="basic-display w-full
                                              rounded-lg border-gray-200
                                              bg-gray-100 text-gray-700
                                              text-sm"
                                       value="0.00"
                                       readonly>

                            </div>

                            {{-- Allowances --}}
                            <div>

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Allowances
                                </label>

                                <input type="number"
                                       name="salaries[{{ $index }}][allowances]"
                                       min="0"
                                       step="0.01"
                                       value="{{ old(
                                           "salaries.$index.allowances",
                                           0
                                       ) }}"
                                       class="allowances-input w-full
                                              rounded-lg border-gray-300
                                              shadow-sm focus:border-blue-500
                                              focus:ring-blue-500 text-sm">

                            </div>

                            {{-- Deductions --}}
                            <div>

                                <label class="block text-sm
                                              font-medium text-gray-700 mb-1">
                                    Deductions
                                </label>

                                <input type="number"
                                       name="salaries[{{ $index }}][deductions]"
                                       min="0"
                                       step="0.01"
                                       value="{{ old(
                                           "salaries.$index.deductions",
                                           0
                                       ) }}"
                                       class="deductions-input w-full
                                              rounded-lg border-gray-300
                                              shadow-sm focus:border-blue-500
                                              focus:ring-blue-500 text-sm">

                            </div>

                        </div>

                        {{-- Net Salary --}}
                        <div class="mt-4 flex justify-end">

                            <div class="text-right">

                                <div class="text-xs text-gray-500">
                                    Net Salary
                                </div>

                                <div class="net-display text-lg
                                            font-semibold text-gray-800">
                                    0.00
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

        <div class="px-5 py-4 bg-gray-50
                    border-t border-gray-200">

            <div class="flex flex-col sm:flex-row
                        sm:items-center sm:justify-between gap-4">

                <div class="text-sm text-gray-500">

                    Total Records:

                    <span id="salary-count"
                          class="font-semibold text-gray-800">
                        {{ count($oldSalaries) }}
                    </span>

                </div>

                <div class="flex items-center justify-end gap-3">

                    <a href="{{ route('salaries.index') }}"
                       class="px-4 py-2 text-sm font-medium
                              text-gray-600 hover:text-gray-800">
                        Cancel
                    </a>

                    <button type="submit"
                            id="save-salaries"
                            class="inline-flex items-center
                                   justify-center px-5 py-2.5
                                   bg-blue-600 hover:bg-blue-700
                                   text-white text-sm font-medium
                                   rounded-lg shadow-sm transition">
                        Save All Salary Records
                    </button>

                </div>

            </div>

        </div>

    </div>

</form>
```

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const rowsContainer =
        document.getElementById('salary-rows');

    const addButton =
        document.getElementById('add-salary');

    const countElement =
        document.getElementById('salary-count');

    const form =
        document.getElementById('salary-form');

    const saveButton =
        document.getElementById('save-salaries');

    let salaryIndex =
        {{ count($oldSalaries) }};

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function updateRow(row) {

        const employeeSelect =
            row.querySelector('.employee-select');

        const basicDisplay =
            row.querySelector('.basic-display');

        const allowancesInput =
            row.querySelector('.allowances-input');

        const deductionsInput =
            row.querySelector('.deductions-input');

        const netDisplay =
            row.querySelector('.net-display');

        let basic = 0;

        if (employeeSelect && employeeSelect.selectedOptions.length) {
            basic = Number(
                employeeSelect.selectedOptions[0]
                    .dataset.basic || 0
            );
        }

        const allowances =
            Number(allowancesInput?.value || 0);

        const deductions =
            Number(deductionsInput?.value || 0);

        const net =
            basic + allowances - deductions;

        if (basicDisplay) {
            basicDisplay.value = formatMoney(basic);
        }

        if (netDisplay) {
            netDisplay.textContent = formatMoney(net);
        }
    }

    function updateRows() {

        const rows =
            rowsContainer.querySelectorAll('.salary-row');

        rows.forEach((row, index) => {

            const number =
                row.querySelector('.row-number');

            if (number) {
                number.textContent = index + 1;
            }

            updateRow(row);
        });

        countElement.textContent = rows.length;
    }

    function createSalaryRow() {

        const index = salaryIndex++;

        const row =
            document.createElement('div');

        row.className =
            'salary-row border border-gray-200 ' +
            'rounded-xl bg-gray-50 overflow-hidden';

        row.dataset.index = index;

        row.innerHTML = `
            <div class="px-4 py-3 bg-gray-100
                        border-b border-gray-200
                        flex items-center justify-between">

                <div class="flex items-center gap-2">

                    <span class="row-number flex items-center
                                 justify-center w-7 h-7 rounded-full
                                 bg-blue-600 text-white text-xs
                                 font-semibold">
                        1
                    </span>

                    <span class="text-sm font-semibold text-gray-700">
                        Salary
                    </span>

                </div>

                <button type="button"
                        class="remove-salary text-sm
                               font-medium text-red-600
                               hover:text-red-800
                               hover:bg-red-50 px-3 py-1.5
                               rounded-lg transition">
                    Remove
                </button>

            </div>

            <div class="p-4">

                <div class="grid grid-cols-1 md:grid-cols-2
                            lg:grid-cols-6 gap-4">

                    <div class="lg:col-span-2">

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Employee
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="salaries[${index}][employee_id]"
                            required
                            class="employee-select w-full rounded-lg
                                   border-gray-300 shadow-sm
                                   focus:border-blue-500
                                   focus:ring-blue-500 text-sm">

                            <option value="">
                                -- Select Employee --
                            </option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    data-basic="{{ $employee->basic_salary }}">
                                    {{ $employee->name }}
                                    ({{ number_format($employee->basic_salary, 2) }})
                                </option>
                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Month
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="salaries[${index}][month]"
                            required
                            class="w-full rounded-lg border-gray-300
                                   shadow-sm focus:border-blue-500
                                   focus:ring-blue-500 text-sm">

                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}"
                                    {{ $month == date('n') ? 'selected' : '' }}>
                                    {{ $month }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Year
                            <span class="text-red-500">*</span>
                        </label>

                        <input type="number"
                               name="salaries[${index}][year]"
                               required
                               min="2000"
                               max="2100"
                               value="{{ date('Y') }}"
                               class="w-full rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">

                    </div>

                    <div>

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Basic Salary
                        </label>

                        <input type="text"
                               class="basic-display w-full
                                      rounded-lg border-gray-200
                                      bg-gray-100 text-gray-700 text-sm"
                               value="0.00"
                               readonly>

                    </div>

                    <div>

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Allowances
                        </label>

                        <input type="number"
                               name="salaries[${index}][allowances]"
                               min="0"
                               step="0.01"
                               value="0"
                               class="allowances-input w-full
                                      rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">

                    </div>

                    <div>

                        <label class="block text-sm
                                      font-medium text-gray-700 mb-1">
                            Deductions
                        </label>

                        <input type="number"
                               name="salaries[${index}][deductions]"
                               min="0"
                               step="0.01"
                               value="0"
                               class="deductions-input w-full
                                      rounded-lg border-gray-300
                                      shadow-sm focus:border-blue-500
                                      focus:ring-blue-500 text-sm">

                    </div>

                </div>

                <div class="mt-4 flex justify-end">

                    <div class="text-right">

                        <div class="text-xs text-gray-500">
                            Net Salary
                        </div>

                        <div class="net-display text-lg
                                    font-semibold text-gray-800">
                            0.00
                        </div>

                    </div>

                </div>

            </div>
        `;

        rowsContainer.appendChild(row);

        updateRows();

        const employeeSelect =
            row.querySelector('.employee-select');

        if (employeeSelect) {
            employeeSelect.focus();
        }
    }

    addButton.addEventListener('click', function () {
        createSalaryRow();
    });

    rowsContainer.addEventListener('click', function (event) {

        const removeButton =
            event.target.closest('.remove-salary');

        if (!removeButton) {
            return;
        }

        const rows =
            rowsContainer.querySelectorAll('.salary-row');

        if (rows.length === 1) {
            return;
        }

        removeButton.closest('.salary-row').remove();

        updateRows();
    });

    rowsContainer.addEventListener('change', function (event) {

        if (
            event.target.classList.contains('employee-select')
        ) {
            updateRow(event.target.closest('.salary-row'));
        }
    });

    rowsContainer.addEventListener('input', function (event) {

        if (
            event.target.classList.contains('allowances-input') ||
            event.target.classList.contains('deductions-input')
        ) {
            updateRow(event.target.closest('.salary-row'));
        }
    });

    form.addEventListener('submit', function () {

        saveButton.disabled = true;

        saveButton.classList.add(
            'opacity-60',
            'cursor-not-allowed'
        );

        saveButton.textContent =
            'Saving Salary Records...';
    });

    updateRows();
});
</script>

@endsection
