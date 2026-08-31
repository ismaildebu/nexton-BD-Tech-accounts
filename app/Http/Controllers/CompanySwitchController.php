<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],
        ]);

        $user = $request->user();

        $company = Company::query()
            ->whereKey((int) $validated['company_id'])
            ->where('status', true)
            ->firstOrFail();

        abort_unless(
            $user->canAccessCompany((int) $company->id),
            403,
            'You do not have access to this company.'
        );

        $financialYear = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        session()->put([
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'financial_year_id' => $financialYear?->id,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Company switched successfully.');
    }
}