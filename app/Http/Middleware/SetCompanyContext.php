<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('company_id')) {

            $company = Company::find(session('company_id'));

            if ($company) {
                app()->instance('currentCompany', $company);
            }
        }

        return $next($request);
    }
}