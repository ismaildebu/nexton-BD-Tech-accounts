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
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        /*
         * Company-scoped user:
         * Always force their own company.
         */
        if ($user->company_id !== null) {
            $company = $user->company;

            if ($company !== null) {
                session([
                    'company_id' => $company->id,
                    'company_name' => $company->company_name,
                ]);
            }

            return $next($request);
        }

        /*
         * Super Admin:
         * Keep the explicitly selected company.
         *
         * If no company has been selected yet, select the first
         * available company as the initial context.
         */
        if (! session()->has('company_id')) {
            $company = Company::query()
                ->where('status', true)
                ->orderBy('id')
                ->first();

            if ($company !== null) {
                session([
                    'company_id' => $company->id,
                    'company_name' => $company->company_name,
                ]);
            }
        }

        return $next($request);
    }
}