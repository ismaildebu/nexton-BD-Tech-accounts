<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Salary;
use App\Services\SalaryAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryController extends Controller
{
    public function index(): View
    {
        $companyId = session('company_id');

        $salaries = Salary::query()
            ->with([
                'employee',
                'paymentAccount',
                'transaction',
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);

        $cashAccount = Account::query()
            ->where('company_id', $companyId)
            ->where('account_name', 'Cash in Hand')
            ->where('is_active', true)
            ->first();

        $bankAccounts = BankAccount::query()
            ->with('account')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->whereHas('account', function ($query) use ($companyId) {
                $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true);
            })
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        return view(
            'salaries.index',
            compact(
                'salaries',
                'cashAccount',
                'bankAccounts'
            )
        );
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('salaries.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salaries' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'salaries.*.employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],

            'salaries.*.month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],

            'salaries.*.year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'salaries.*.allowances' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'salaries.*.deductions' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $companyId = session('company_id');

        if (! $companyId) {
            return back()
                ->withInput()
                ->withErrors([
                    'salaries' => 'No company is currently selected.',
                ]);
        }

        DB::transaction(function () use (
            $validated,
            $companyId
        ): void {
            $batchKeys = [];

            foreach ($validated['salaries'] as $index => $salaryData) {
                $employee = Employee::query()
                    ->where('company_id', $companyId)
                    ->findOrFail((int) $salaryData['employee_id']);

                if (
                    (int) $employee->company_id
                    !== (int) $companyId
                ) {
                    throw ValidationException::withMessages([
                        "salaries.{$index}.employee_id" =>
                            'Selected employee does not belong to the current company.',
                    ]);
                }

                $month = (int) $salaryData['month'];
                $year = (int) $salaryData['year'];

                $batchKey =
                    $employee->id . '-' . $month . '-' . $year;

                if (isset($batchKeys[$batchKey])) {
                    throw ValidationException::withMessages([
                        "salaries.{$index}.employee_id" =>
                            "Duplicate salary record submitted for {$employee->name} for {$month}/{$year}.",
                    ]);
                }

                $batchKeys[$batchKey] = true;

                $alreadyExists = Salary::query()
                    ->where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->exists();

                if ($alreadyExists) {
                    throw ValidationException::withMessages([
                        "salaries.{$index}.employee_id" =>
                            "A salary record already exists for {$employee->name} for {$month}/{$year}.",
                    ]);
                }

                $allowances =
                    (float) ($salaryData['allowances'] ?? 0);

                $deductions =
                    (float) ($salaryData['deductions'] ?? 0);

                $basic =
                    (float) $employee->basic_salary;

                $netSalary =
                    $basic + $allowances - $deductions;

                if ($netSalary < 0) {
                    throw ValidationException::withMessages([
                        "salaries.{$index}.deductions" =>
                            'Deductions cannot exceed the total salary amount.',
                    ]);
                }

                Salary::create([
                    'company_id' => (int) $companyId,
                    'employee_id' => $employee->id,
                    'month' => $month,
                    'year' => $year,
                    'basic_salary' => $basic,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                ]);
            }
        });

        $count = count($validated['salaries']);

        return redirect()
            ->route('salaries.index')
            ->with(
                'success',
                $count === 1
                    ? 'Salary record created successfully.'
                    : "{$count} salary records created successfully."
            );
    }

    public function show(Salary $salary): View
    {
        $salary->load([
            'employee',
            'paymentAccount',
            'transaction',
        ]);

        return view(
            'salaries.show',
            compact('salary')
        );
    }

    public function destroy(Salary $salary): RedirectResponse
    {
        if ($salary->status === 'paid') {
            return back()->with(
                'error',
                'Paid salary records cannot be deleted.'
            );
        }

        $salary->delete();

        return redirect()
            ->route('salaries.index')
            ->with(
                'success',
                'Salary record deleted successfully.'
            );
    }

    public function markPaid(
        Request $request,
        Salary $salary,
        SalaryAccountingService $salaryAccountingService
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_account_id' => [
                'required',
                'integer',
            ],
        ]);

        $companyId = session('company_id');

        if (! $companyId) {
            return back()
                ->withInput()
                ->withErrors([
                    'payment_account_id' =>
                        'No company is currently selected.',
                ]);
        }

        if (
            (int) $salary->company_id
            !== (int) $companyId
        ) {
            abort(404);
        }

        if ($salary->transaction_id !== null) {
            return back()->with(
                'error',
                'This salary already has an accounting transaction.'
            );
        }

        if ($salary->status === 'paid') {
            return back()->with(
                'error',
                'This salary record is already marked as paid.'
            );
        }

        try {
            $transaction =
                $salaryAccountingService->postPayment(
                    $salary,
                    (int) $validated['payment_account_id']
                );
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route('salaries.index')
            ->with(
                'success',
                'Salary marked as paid and Payment Voucher '
                . $transaction->voucher_number
                . ' created successfully.'
            );
    }
}