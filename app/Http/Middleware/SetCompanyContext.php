<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $companyId = session('company_id');

        if ($companyId === null) {
            app()->forgetInstance('currentCompany');

            return $next($request);
        }

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

            app()->forgetInstance('currentCompany');

            return $next($request);
        }

        /*
         * Defence in depth:
         * Never trust a company ID merely because it exists in the session.
         */
        if (! $user->canAccessCompany((int) $company->id)) {
            session()->forget([
                'company_id',
                'company_name',
                'financial_year_id',
            ]);

            app()->forgetInstance('currentCompany');

            abort(
                403,
                'You do not have access to this company.'
            );
        }

        app()->instance('currentCompany', $company);

        return $next($request);
    }
}