<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {

            // যদি session এ company না থাকে
            if (!session()->has('company_id')) {

                $company = Company::first();

                if ($company) {
                    session([
                        'company_id' => $company->id,
                        'company_name' => $company->company_name,
                    ]);
                }
            }

        }

        return $next($request);
    }
}