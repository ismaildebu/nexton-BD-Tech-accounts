<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Class SalaryController
 *
 * এই কন্ট্রোলার Salary (Payroll) মডিউলের সম্পূর্ণ CRUD অপারেশন হ্যান্ডেল করে।
 * net_salary সবসময় সার্ভার সাইডে অটো-ক্যালকুলেট হয় (basic + allowances -
 * deductions), ব্যবহারকারীর ইনপুট থেকে নেওয়া হয় না — যাতে ডেটা সবসময়
 * সামঞ্জস্যপূর্ণ থাকে।
 */
class SalaryController extends Controller
{
    /**
     * সকল বেতন রেকর্ডের তালিকা প্রদর্শন করে।
     */
    public function index()
    {
        $salaries = Salary::with('employee')
            ->latest('salary_month')
            ->paginate(15);

        return view('salaries.index', compact('salaries'));
    }

    /**
     * নতুন বেতন এন্ট্রির ফর্ম প্রদর্শন করে।
     */
    public function create()
    {
        $employees = Employee::orderBy('name')->get();

        return view('salaries.create', compact('employees'));
    }

    /**
     * নতুন বেতন রেকর্ড সংরক্ষণ করে।
     * Validation: basic_salary >= 0, allowances >= 0, deductions >= 0
     * net_salary সার্ভারে অটো-ক্যালকুলেট হয়।
     */
    public function store(Request $request)
    {
        $validated = $this->validateSalary($request);

        $validated['net_salary'] = Salary::calculateNetSalary(
            $validated['basic_salary'],
            $validated['allowances'],
            $validated['deductions']
        );

        Salary::create($validated);

        return redirect()
            ->route('salaries.index')
            ->with('success', 'Salary record created successfully.');
    }

    /**
     * নির্দিষ্ট বেতন রেকর্ডের বিস্তারিত তথ্য প্রদর্শন করে।
     */
    public function show(Salary $salary)
    {
        $salary->load('employee');

        return view('salaries.show', compact('salary'));
    }

    /**
     * বিদ্যমান বেতন রেকর্ড এডিট করার ফর্ম প্রদর্শন করে।
     */
    public function edit(Salary $salary)
    {
        $employees = Employee::orderBy('name')->get();

        return view('salaries.edit', compact('salary', 'employees'));
    }

    /**
     * নির্দিষ্ট বেতন রেকর্ড আপডেট করে।
     * net_salary পুনরায় ক্যালকুলেট করা হয়।
     */
    public function update(Request $request, Salary $salary)
    {
        $validated = $this->validateSalary($request, $salary->id);

        $validated['net_salary'] = Salary::calculateNetSalary(
            $validated['basic_salary'],
            $validated['allowances'],
            $validated['deductions']
        );

        $salary->update($validated);

        return redirect()
            ->route('salaries.show', $salary)
            ->with('success', 'Salary record updated successfully.');
    }

    /**
     * নির্দিষ্ট বেতন রেকর্ড মুছে ফেলে।
     */
    public function destroy(Salary $salary)
    {
        $salary->delete();

        return redirect()
            ->route('salaries.index')
            ->with('success', 'Salary record deleted successfully.');
    }

    /**
     * store() ও update() উভয়ের জন্য পুনঃব্যবহারযোগ্য validation রুল।
     * একই employee_id + salary_month এর ডুপ্লিকেট এন্ট্রি আটকানো হয়
     * (raw DB unique constraint error এর বদলে friendly ভ্যালিডেশন
     * মেসেজ দেখানোর জন্য)।
     *
     * @param  int|null $ignoreSalaryId  আপডেটের সময় নিজের রেকর্ডকে
     *                                    unique চেক থেকে বাদ দিতে
     * @return array<string, mixed>
     */
    private function validateSalary(Request $request, ?int $ignoreSalaryId = null): array
    {
        return $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'basic_salary'  => ['required', 'numeric', 'min:0'],
            'allowances'    => ['nullable', 'numeric', 'min:0'],
            'deductions'    => ['nullable', 'numeric', 'min:0'],
            'salary_month'  => [
                'required',
                'date',
                Rule::unique('salaries', 'salary_month')
                    ->where(fn ($query) => $query->where('employee_id', $request->input('employee_id')))
                    ->ignore($ignoreSalaryId),
            ],
        ], [
            'basic_salary.min'    => 'Basic salary must be greater than or equal to 0.',
            'salary_month.unique' => 'A salary record already exists for this employee in the selected month.',
        ]);
    }
}

/**
 * ------------------------------------------------------------------
 * routes/web.php এ যুক্ত করতে হবে:
 * ------------------------------------------------------------------
 * Route::resource('salaries', SalaryController::class);
 *
 * (পূর্ণ resource route — edit() মেথড ও resources/views/salaries/edit.blade.php
 * সাপোর্টিং ফাইল হিসেবে পরে যুক্ত করা হয়েছে।)
 *
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   Salary এর CRUD অপারেশন, validation এবং net_salary অটো-ক্যালকুলেশন
 *   নিশ্চিত করা।
 *
 * টেস্টিং ধাপ:
 *   1. GET /salaries → তালিকা প্রদর্শিত হচ্ছে কিনা যাচাই করুন।
 *   2. GET /salaries/create → ফর্ম লোড হচ্ছে কিনা দেখুন।
 *   3. POST /salaries → basic_salary=-100 দিয়ে সাবমিট করে validation
 *      error আসছে কিনা যাচাই করুন।
 *   4. basic_salary=30000, allowances=5000, deductions=2000 দিয়ে
 *      সাবমিট করে net_salary=33000.00 সংরক্ষিত হচ্ছে কিনা যাচাই করুন।
 *   5. deductions বেশি (basic+allowances এর চেয়ে) দিয়ে সাবমিট করে
 *      নেগেটিভ net_salary সংরক্ষিত হচ্ছে কিনা এবং UI তে লাল রঙে
 *      হাইলাইট হচ্ছে কিনা যাচাই করুন।
 *   6. PUT /salaries/{id} → রেকর্ড আপডেট করে net_salary পুনরায়
 *      সঠিকভাবে ক্যালকুলেট হচ্ছে কিনা যাচাই করুন।
 *   7. DELETE /salaries/{id} → রেকর্ড মুছে যাচ্ছে কিনা যাচাই করুন।
 * ------------------------------------------------------------------
 */