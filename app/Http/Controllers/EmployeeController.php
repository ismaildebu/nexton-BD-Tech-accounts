<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $companyId = session('company_id');

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('name')
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('employees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employees' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'employees.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'employees.*.designation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'employees.*.department' => [
                'nullable',
                'string',
                'max:255',
            ],

            'employees.*.phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'employees.*.alternate_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'employees.*.nid_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'employees.*.joining_date' => [
                'nullable',
                'date',
            ],

            'employees.*.basic_salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'employees.*.photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $companyId = session('company_id');

        if (!$companyId) {
            return back()
                ->withInput()
                ->withErrors([
                    'employees' => 'No company is currently selected.',
                ]);
        }

        $storedPhotos = [];

        try {
            DB::transaction(function () use (
                $validated,
                $request,
                $companyId,
                &$storedPhotos
            ): void {
                foreach ($validated['employees'] as $index => $employeeData) {
                    $photoPath = null;

                    if ($request->hasFile("employees.$index.photo")) {
                        $photoPath = $request
                            ->file("employees.$index.photo")
                            ->store(
                                "employees/{$companyId}/photos",
                                'local'
                            );

                        $storedPhotos[] = $photoPath;
                    }

                    Employee::create([
                        'company_id' => $companyId,
                        'name' => $employeeData['name'],
                        'designation' => $employeeData['designation'] ?? null,
                        'department' => $employeeData['department'] ?? null,
                        'phone' => $employeeData['phone'] ?? null,
                        'alternate_phone' => $employeeData['alternate_phone'] ?? null,
                        'nid_number' => $employeeData['nid_number'] ?? null,
                        'photo_path' => $photoPath,
                        'joining_date' => $employeeData['joining_date'] ?? null,
                        'basic_salary' => $employeeData['basic_salary'],
                        'is_active' => true,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPhotos as $photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }

        $count = count($validated['employees']);

        return redirect()
            ->route('employees.index')
            ->with(
                'success',
                $count === 1
                    ? 'Employee added successfully.'
                    : "{$count} employees added successfully."
            );
    }

    public function show(Employee $employee): View
    {
        $employee->load('salaries');

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'designation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'department' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'alternate_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'nid_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'joining_date' => [
                'nullable',
                'date',
            ],

            'basic_salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $oldPhotoPath = $employee->photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('photo')) {
            $newPhotoPath = $request
                ->file('photo')
                ->store(
                    "employees/{$employee->company_id}/photos",
                    'local'
                );

            $validated['photo_path'] = $newPhotoPath;
        }

        unset($validated['photo']);

        $validated['is_active'] = $request->has('is_active');

        try {
            DB::transaction(function () use (
                $employee,
                $validated
            ): void {
                $employee->update($validated);
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $photoPath = $employee->photo_path;

        DB::transaction(function () use ($employee): void {
            $employee->delete();
        });

        if ($photoPath) {
            Storage::disk('local')->delete($photoPath);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}