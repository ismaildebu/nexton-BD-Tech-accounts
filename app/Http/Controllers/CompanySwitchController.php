<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->company_id;
        $user = $request->user();

        // Only Super Admin may switch into an arbitrary company. Everyone
        // else is locked to their own company_id.
        if (! $user->isSuperAdmin() && $companyId !== $user->company_id) {
            abort(403, 'You do not have access to this company.');
        }

        $financialYear = FinancialYear::where('company_id', $companyId)
            ->where('is_active', true)
            ->latest()
            ->first();

        session([
            'company_id'        => $companyId,
            'financial_year_id' => $financialYear?->id,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Company switched successfully.');
    }
}