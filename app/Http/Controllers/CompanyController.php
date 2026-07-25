<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Account;
use App\Models\AccountTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Company List
     */
    public function index()
    {
        $companies = Company::latest()->get();

        return view('companies.index', compact('companies'));
    }

    /**
     * Create Company Form
     */
    public function create()
    {
        $accountTemplates = AccountTemplate::orderBy('account_code')->get();

        return view('companies.create', compact('accountTemplates'));
    }

    /**
     * Store Company
     */
    
    public function store(Request $request)
{
    
$request->validate([
    'company_name'  => 'required|string|max:255',
    'business_type' => 'required|string',

    'accounts'      => 'required|array|min:1',
    'accounts.*'    => 'exists:account_templates,id',
]);

    DB::transaction(function () use ($request) {

        $company = Company::create([
            'company_name'  => $request->company_name,
            'business_type' => $request->business_type,
        ]);

        $templates = AccountTemplate::whereIn('id', $request->accounts)
    ->orderBy('account_code')
    ->get();

        foreach ($templates as $template) {

            Account::create([
                'company_id'      => $company->id,
                'account_code'    => $template->account_code,
                'account_name'    => $template->account_name,
                'account_type'    => $template->account_type,
                'nature'          => $template->nature,
                'parent_id'       => null,
                'level'           => 1,
                'color'           => null,
                'is_system'       => $template->is_system,
                'is_active'       => $template->is_active,
                'opening_balance' => 0,
                'balance_type'    => $template->balance_type,
            ]);

        }

    });

    return redirect()
        ->route('companies.index')
        ->with('success', 'Company created successfully.');
}

    /**
     * Edit Company
     */
    public function edit(string $id)
    {
        $company = Company::findOrFail($id);

        return view('companies.edit', compact('company'));
    }

    /**
     * Update Company
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'company_name'  => 'required|string|max:255',
            'business_type' => 'required|string',
        ]);

        $company = Company::findOrFail($id);

        $company->update([
            'company_name'  => $request->company_name,
            'business_type' => $request->business_type,
        ]);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Delete Company
     */
    public function destroy(string $id)
    {
        $company = Company::findOrFail($id);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}