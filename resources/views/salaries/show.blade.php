{{--
    Salaries - Show View
    একটি নির্দিষ্ট বেতন রেকর্ডের বিস্তারিত তথ্য প্রদর্শন করে।
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Salary Details</h1>
        <a href="{{ route('salaries.index') }}" class="text-sm text-blue-600 hover:underline">
            &larr; Back to list
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded-md bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left w-1/3 bg-gray-50 font-medium text-gray-600">Employee</th>
                    <td class="px-4 py-3 text-gray-800">{{ $salary->employee->name ?? '—' }}</td>
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
                    <td class="px-4 py-3 font-semibold {{ $salary->net_salary < 0 ? 'salary-negative' : 'text-gray-800' }}">
                        {{ number_format($salary->net_salary, 2) }}
                    </td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Salary Month</th>
                    <td class="px-4 py-3 text-gray-800">{{ $salary->salary_month->format('F, Y') }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left bg-gray-50 font-medium text-gray-600">Recorded At</th>
                    <td class="px-4 py-3 text-gray-500">{{ $salary->created_at->format('d M, Y h:i A') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <form action="{{ route('salaries.destroy', $salary) }}" method="POST"
              onsubmit="return confirm('Delete this salary record?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow transition">
                Delete Salary
            </button>
        </form>
    </div>
</div>
@endsection

{{--
    ------------------------------------------------------------------
    ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
    ------------------------------------------------------------------
    উদ্দেশ্য:
        একটি নির্দিষ্ট বেতন রেকর্ডের সম্পূর্ণ বিস্তারিত প্রদর্শন।

    টেস্টিং ধাপ:
        1. index পেজ থেকে যেকোনো "View" লিংকে ক্লিক করুন।
        2. সকল ফিল্ড (employee, basic, allowances, deductions, net,
           month) সঠিক দেখাচ্ছে কিনা যাচাই করুন।
        3. net_salary ঋণাত্মক হলে লাল রঙে হাইলাইট হচ্ছে কিনা দেখুন।
        4. "Delete Salary" বাটনে ক্লিক করে confirm dialog ও পরবর্তীতে
           index পেজে redirect হচ্ছে কিনা যাচাই করুন।
    ------------------------------------------------------------------
--}}