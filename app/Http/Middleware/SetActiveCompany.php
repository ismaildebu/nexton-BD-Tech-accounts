<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        /*
         * Company-scoped users are always locked to their own company.
         */
        if ($user->company_id !== null) {
            $company = $user->company;

            abort_unless(
                $company !== null && $company->status,
                403,
                'Your company is not available.'
            );

            session([
                'company_id' => $company->id,
                'company_name' => $company->company_name,
            ]);

            return $next($request);
        }

        /*
         * Super Admin may work with an explicitly selected company.
         */
        if ($user->isSuperAdmin()) {
            $companyId = session('company_id');

            if ($companyId !== null) {
                $company = Company::query()
                    ->whereKey((int) $companyId)
                    ->where('status', true)
                    ->first();

                if ($company === null) {
                    session()->forget([
                        'company_id',
                        'company_name',
                        'financial_year_id',
                    ]);
                } else {
                    session([
                        'company_id' => $company->id,
                        'company_name' => $company->company_name,
                    ]);
                }
            }

            return $next($request);
        }

        return $next($request);
    }
}