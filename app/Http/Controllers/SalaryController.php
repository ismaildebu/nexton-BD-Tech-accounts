<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryController extends Controller
{
    /**
     * Display salary records for the current company.
     *
     * BelongsToCompany global scope স্বয়ংক্রিয়ভাবে company filter করে।
     */
    public function index(): View
    {
        $salaries = Salary::with('employee')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);

        return view('salaries.index', compact('salaries'));
    }

    /**
     * Show the salary creation form.
     *
     * Employee::where('company_id',...) এর পরিবর্তে global scope কাজ করছে।
     */
    public function create(): View
    {
        $employees = Employee::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('salaries.create', compact('employees'));
    }

    /**
     * Store a new salary record.
     *
     * ✅ Fix: Employee cross-company IDOR বন্ধ করা হয়েছে।
     *
     * আগে শুধু 'exists:employees,id' দিয়ে validate করা হত।
     * এর ফলে অন্য company-র employee_id দিলেও salary তৈরি হত।
     *
     * এখন Employee fetch-এ BelongsToCompany global scope কাজ করে —
     * অন্য company-র employee হলে findOrFail() 404 দেবে।
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'month'       => ['required', 'integer', 'min:1', 'max:12'],
            'year'        => ['required', 'integer', 'min:2000', 'max:2100'],
            'allowances'  => ['nullable', 'numeric', 'min:0'],
            'deductions'  => ['nullable', 'numeric', 'min:0'],
        ]);

        // ✅ BelongsToCompany global scope সক্রিয় — অন্য company-র
        // employee_id দিলে এখানেই 404, salary তৈরি হবে না।
        $employee = Employee::findOrFail($validated['employee_id']);

        // Duplicate salary check — same company scope-এর মধ্যে
        $alreadyExists = Salary::where('employee_id', $employee->id)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withErrors(['employee_id' => 'A salary record for this employee already exists for the selected month/year.'])
                ->withInput();
        }

        $allowances = (float) ($validated['allowances'] ?? 0);
        $deductions = (float) ($validated['deductions'] ?? 0);
        $basic      = (float) $employee->basic_salary;

        Salary::create([
            'company_id'   => (int) session('company_id'),
            'employee_id'  => $employee->id,
            'month'        => $validated['month'],
            'year'         => $validated['year'],
            'basic_salary' => $basic,
            'allowances'   => $allowances,
            'deductions'   => $deductions,
            'net_salary'   => $basic + $allowances - $deductions,
            'status'       => 'pending',
        ]);

        return redirect()
            ->route('salaries.index')
            ->with('success', 'Salary record created successfully.');
    }

    /**
     * Display the specified salary record.
     *
     * Route model binding + BelongsToCompany scope — অন্য company-র
     * salary ID দিলে automatic 404। Manual authorizeCompany() দরকার নেই।
     */
    public function show(Salary $salary): View
    {
        $salary->load('employee');

        return view('salaries.show', compact('salary'));
    }

    /**
     * Delete a salary record.
     *
     * শুধুমাত্র 'pending' salary delete করা যাবে।
     */
    public function destroy(Salary $salary): RedirectResponse
    {
        if ($salary->status === 'paid') {
            return back()->with('error', 'Paid salary records cannot be deleted.');
        }

        $salary->delete();

        return redirect()
            ->route('salaries.index')
            ->with('success', 'Salary record deleted successfully.');
    }

    /**
     * Mark a salary record as paid.
     */
    public function markPaid(Request $request, Salary $salary): RedirectResponse
    {
        if ($salary->status === 'paid') {
            return back()->with('error', 'This salary record is already marked as paid.');
        }

        $salary->update([
            'status'    => 'paid',
            'paid_date' => now()->toDateString(),
        ]);

        return redirect()
            ->route('salaries.index')
            ->with('success', 'Salary marked as paid.');
    }
}