<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('name')
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'designation'   => ['nullable', 'string', 'max:255'],
            'department'    => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'joining_date'  => ['nullable', 'date'],
            'basic_salary'  => ['required', 'numeric', 'min:0'],
        ]);

        $validated['company_id'] = session('company_id');

        Employee::create($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load('salaries');

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'designation'   => ['nullable', 'string', 'max:255'],
            'department'    => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'joining_date'  => ['nullable', 'date'],
            'basic_salary'  => ['required', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $employee->update($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}