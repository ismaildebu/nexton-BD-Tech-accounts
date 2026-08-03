{{--
    Salaries - Edit View
    বিদ্যমান বেতন রেকর্ড এডিট করার ফর্ম। create.blade.php এর মতোই
    গঠন, তবে পূর্বের মান দিয়ে prefilled এবং PUT মেথড ব্যবহার করে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Edit Salary Entry</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('salaries.update', $salary) }}" method="POST"
          class="transfer-form bg-white p-6 rounded-lg shadow ring-1 ring-gray-200 space-y-5">
        @csrf
        @method('PUT')

        {{-- কর্মচারী নির্বাচন --}}
        <div>
            <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select name="employee_id" id="employee_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $salary->employee_id) == $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- বেসিক বেতন --}}
        <div>
            <label for="basic_salary" class="block text-sm font-medium text-gray-700 mb-1">Basic Salary</label>
            <input type="number" step="0.01" min="0" name="basic_salary" id="basic_salary" required
                   value="{{ old('basic_salary', $salary->basic_salary) }}"
                   class="salary-calc-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        {{-- ভাতা --}}
        <div>
            <label for="allowances" class="block text-sm font-medium text-gray-700 mb-1">Allowances</label>
            <input type="number" step="0.01" min="0" name="allowances" id="allowances"
                   value="{{ old('allowances', $salary->allowances) }}"
                   class="salary-calc-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        {{-- কর্তন --}}
        <div>
            <label for="deductions" class="block text-sm font-medium text-gray-700 mb-1">Deductions</label>
            <input type="number" step="0.01" min="0" name="deductions" id="deductions"
                   value="{{ old('deductions', $salary->deductions) }}"
                   class="salary-calc-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        {{-- বেতনের মাস --}}
        <div>
            <label for="salary_month" class="block text-sm font-medium text-gray-700 mb-1">Salary Month</label>
            <input type="month" name="salary_month" id="salary_month" required
                   value="{{ old('salary_month', $salary->salary_month->format('Y-m')) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        {{-- লাইভ নেট স্যালারি প্রিভিউ --}}
        <div class="pt-2 border-t border-gray-100">
            <p class="text-sm text-gray-500">Estimated Net Salary</p>
            <p id="net_salary_preview" class="text-lg font-semibold text-gray-800">
                {{ number_format($salary->net_salary, 2) }}
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('salaries.show', $salary) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
                Update Salary
            </button>
        </div>
    </form>
</div>

<script>
    // শুধুমাত্র UI প্রিভিউর জন্য — চূড়ান্ত net_salary সবসময় সার্ভারে
    // (SalaryController@update) পুনরায় ক্যালকুলেট ও যাচাই করা হয়।
    document.addEventListener('DOMContentLoaded', function () {
        const basic = document.getElementById('basic_salary');
        const allowances = document.getElementById('allowances');
        const deductions = document.getElementById('deductions');
        const preview = document.getElementById('net_salary_preview');

        function updatePreview() {
            const net = (parseFloat(basic.value) || 0)
                + (parseFloat(allowances.value) || 0)
                - (parseFloat(deductions.value) || 0);

            preview.textContent = net.toFixed(2);
            preview.classList.toggle('salary-negative', net < 0);
        }

        [basic, allowances, deductions].forEach(function (input) {
            input.addEventListener('input', updatePreview);
        });
    });
</script>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        বিদ্যমান বেতন রেকর্ড এডিট করা এবং net_salary পুনরায়
        ক্যালকুলেট নিশ্চিত করা।

    টেস্টিং ধাপ:
        1. /salaries/{id}/edit ভিজিট করুন — ফর্ম আগের মান দিয়ে
           prefilled আছে কিনা যাচাই করুন।
        2. basic_salary/allowances/deductions পরিবর্তন করে "Update
           Salary" চাপুন — /salaries/{id} এ redirect হয়ে নতুন
           net_salary দেখাচ্ছে কিনা যাচাই করুন।
        3. অন্য একটি employee_id + একই salary_month এর সাথে conflict
           করলে (যদি duplicate validation যোগ করা থাকে) error message
           দেখাচ্ছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}