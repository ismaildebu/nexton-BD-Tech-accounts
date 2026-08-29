<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\FinancialYear;
use App\Models\Company;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialYearController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    
    public function index()
{
    $financialYears = \App\Models\FinancialYear::with('company')
        ->where('company_id', session('company_id'))
        ->latest()
        ->get();

    return view('financial-years.index', compact('financialYears'));
}

    /**
     * Show the form for creating a new resource.
     */
   
    public function create()
{
    return view('financial-years.create');
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $companyId = session('company_id');

    $request->validate([
        'year_name'  => [
            'required',
            Rule::unique('financial_years', 'year_name')->where('company_id', $companyId),
        ],
        'start_date' => 'required|date',
        'end_date'   => 'required|date|after:start_date',
    ]);

    $this->enforcePlanLimit(
        $this->planLimitService,
        $companyId,
        'financial_years',
        FinancialYear::where('company_id', $companyId)->count(),
    );

    \App\Models\FinancialYear::create([
        'company_id' => $companyId,
        'year_name'  => $request->year_name,
        'start_date' => $request->start_date,
        'end_date'   => $request->end_date,
        'is_active'  => $request->is_active ?? 0,
        'is_closed'  => $request->is_closed ?? 0,
    ]);

    return redirect()
        ->route('financial-years.index')
        ->with('success', 'Financial Year created successfully.');
}
    

    /**
     * Display the specified resource.
     */
   
    public function show(FinancialYear $financialYear)
{
    return view('financial-years.show', compact('financialYear'));
}

    /**
     * Show the form for editing the specified resource.
     */
   
    public function edit(FinancialYear $financialYear)
{
    return view('financial-years.edit', compact('financialYear'));
}

    /**
     * Update the specified resource in storage.
     */
  
    public function update(Request $request, FinancialYear $financialYear)
{
    $companyId = session('company_id');

    $request->validate([
        'year_name'  => [
            'required',
            Rule::unique('financial_years', 'year_name')
                ->where('company_id', $companyId)
                ->ignore($financialYear->id),
        ],
        'start_date' => 'required|date',
        'end_date'   => 'required|date|after:start_date',
    ]);


    $financialYear->update([
        'year_name' => $request->year_name,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'is_active' => $request->is_active ?? 0,
        'is_closed' => $request->is_closed ?? 0,
    ]);


    return redirect()
        ->route('financial-years.index')
        ->with('success', 'Financial Year updated successfully.');
}

    /**
     * Remove the specified resource from storage.
     */
   public function destroy(FinancialYear $financialYear)
{
    $financialYear->delete();

    return redirect()
        ->route('financial-years.index')
        ->with('success', 'Financial Year deleted successfully.');
}
    
}