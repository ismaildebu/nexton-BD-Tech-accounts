<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    /**
     * Company List
     * Super Admin -> all companies. Everyone else -> only their own.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $companies = $user->isSuperAdmin()
            ? Company::latest()->get()
            : Company::whereKey($user->company_id)->get();

        return view('companies.index', compact('companies'));
    }

    /**
     * Create Company Form — Super Admin only (route + permission enforced).
     */
    public function create()
    {
        $accountTemplates = AccountTemplate::orderBy('account_code')->get();

        return view('companies.create', compact('accountTemplates'));
    }

    /**
     * Store Company — Super Admin only.
     * Also creates the company's Admin user in the same transaction —
     * that user is auto-locked to this new company (company_id).
     */
    public function store(Request $request)
    {
        // Defence in depth: even if the permission/middleware is ever
        // mis-configured, only a Super Admin can create a company.
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin can create a new company.');

        $request->validate([
            'company_name'  => 'required|string|max:255',
            'business_type' => 'required|string',

            'accounts'      => 'required|array|min:1',
            'accounts.*'    => 'exists:account_templates,id',

            'admin_name'                  => 'required|string|max:255',
            'admin_email'                 => 'required|email|max:255|unique:users,email',
            'admin_password'              => 'required|string|min:6|confirmed',
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

            // Auto-create this company's Admin account, locked to it.
            $admin = User::create([
                'name'       => $request->admin_name,
                'email'      => $request->admin_email,
                'password'   => Hash::make($request->admin_password),
                'role'       => 'Admin',
                'status'     => true,
                'company_id' => $company->id,
            ]);

            $admin->syncRoles(['admin']);
        });

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company and its admin account were created successfully.');
    }

    /**
     * Show Company Details — Super Admin (any) or Admin (own company only).
     */
    public function show(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

        $this->authorizeCompanyAccess($request, $company);

        return view('companies.show', compact('company'));
    }

    /**
     * Edit Company — Super Admin (any) or Admin (own company only).
     */
    public function edit(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

        $this->authorizeCompanyAccess($request, $company);

        return view('companies.edit', compact('company'));
    }

    /**
     * Update Company — Super Admin (any) or Admin (own company only).
     */
    public function update(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

        $this->authorizeCompanyAccess($request, $company);

        $request->validate([
            'company_name'  => 'required|string|max:255',
            'business_type' => 'required|string',
        ]);

        $company->update([
            'company_name'  => $request->company_name,
            'business_type' => $request->business_type,
        ]);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Delete Company — Super Admin only.
     */
    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin can delete a company.');

        $company = Company::findOrFail($id);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    /**
     * Super Admin can access every company. Anyone else only their own
     * (matched against their locked company_id).
     */
    private function authorizeCompanyAccess(Request $request, Company $company): void
    {
        abort_unless(
            $request->user()->canAccessCompany($company->id),
            403,
            'You do not have access to this company.'
        );
    }
}
