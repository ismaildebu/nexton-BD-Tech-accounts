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
        $companyId = session('company_id');

        if ($companyId !== null) {
            $company = Company::query()->find($companyId);

            if ($company !== null) {
                app()->instance('currentCompany', $company);
            }
        }

        return $next($request);
    }
}