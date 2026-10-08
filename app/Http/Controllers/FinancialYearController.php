<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use App\Services\PlanLimitService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialYearController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly FinancialYearService $financialYears,
    ) {
    }

    public function index()
    {
        $financialYears = FinancialYear::with('company')
            ->where('company_id', session('company_id'))
            ->latest()
            ->get();

        return view('financial-years.index', compact('financialYears'));
    }

    public function create()
    {
        return view('financial-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) session('company_id');

        $request->validate([
            'year_name' => [
                'required',
                Rule::unique('financial_years', 'year_name')->where('company_id', $companyId),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'financial_years',
            FinancialYear::where('company_id', $companyId)->count(),
        );

        try {
            $this->financialYears->create($companyId, [
                'year_name' => $request->input('year_name'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'is_active' => $request->boolean('is_active'),
            ]);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['financial_year' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-years.index')
            ->with('success', 'Financial Year created successfully.');
    }

    public function show(FinancialYear $financialYear)
    {
        return view('financial-years.show', compact('financialYear'));
    }

    public function edit(FinancialYear $financialYear)
    {
        return view('financial-years.edit', compact('financialYear'));
    }

    public function update(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $companyId = (int) session('company_id');

        $request->validate([
            'year_name' => [
                'required',
                Rule::unique('financial_years', 'year_name')
                    ->where('company_id', $companyId)
                    ->ignore($financialYear->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            // is_closed is deliberately NOT accepted here: use close()/reopen().
            $this->financialYears->update($financialYear, [
                'year_name' => $request->input('year_name'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'is_active' => $request->boolean('is_active'),
            ]);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['financial_year' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-years.index')
            ->with('success', 'Financial Year updated successfully.');
    }

    public function destroy(FinancialYear $financialYear): RedirectResponse
    {
        try {
            $this->financialYears->delete($financialYear);
        } catch (DomainException $e) {
            return back()->withErrors(['financial_year' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-years.index')
            ->with('success', 'Financial Year deleted successfully.');
    }

    public function close(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        try {
            $this->financialYears->close($financialYear, (int) $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['financial_year' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-years.index')
            ->with('success', 'Financial Year closed.');
    }

    public function reopen(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->financialYears->reopen(
                $financialYear,
                (int) $request->user()->id,
                $validated['reason']
            );
        } catch (DomainException $e) {
            return back()->withErrors(['financial_year' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-years.index')
            ->with('success', 'Financial Year reopened.');
    }
}
