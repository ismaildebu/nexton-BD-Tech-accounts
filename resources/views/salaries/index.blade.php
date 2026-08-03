{{--
    Salaries - Index View
    সকল বেতন রেকর্ডের তালিকা প্রদর্শন করে।
    TailwindCSS দিয়ে রেসপন্সিভ টেবিল লেআউট ব্যবহার করা হয়েছে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800">Salaries</h1>

        <a href="{{ route('salaries.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow transition">
            + New Salary
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
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Employee</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Basic</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Allowances</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Deductions</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Net Salary</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Month</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($salaries as $salary)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $salary->employee->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($salary->basic_salary, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($salary->allowances, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($salary->deductions, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $salary->net_salary < 0 ? 'salary-negative' : 'text-gray-800' }}">
                            {{ number_format($salary->net_salary, 2) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $salary->salary_month->format('M, Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('salaries.show', $salary) }}"
                               class="text-blue-600 hover:underline">View</a>

                            <form action="{{ route('salaries.destroy', $salary) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Delete this salary record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">No salary records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $salaries->links() }}
    </div>
</div>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        Salary তালিকা প্রদর্শন, পেজিনেশন এবং ডিলিট action।

    টেস্টিং ধাপ:
        1. ব্রাউজারে /salaries ভিজিট করুন।
        2. টেবিলে সকল কলাম (basic, allowances, deductions, net) সঠিক
           সংখ্যা ফরম্যাটে দেখাচ্ছে কিনা যাচাই করুন।
        3. net_salary ঋণাত্মক হলে লাল রঙে হাইলাইট হচ্ছে কিনা দেখুন
           (payroll.css এর salary-negative ক্লাস চেক করুন)।
        4. "New Salary" বাটনে ক্লিক করে create পেজে যাচ্ছে কিনা যাচাই করুন।
        5. Delete বাটনে ক্লিক করে confirm dialog আসছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}