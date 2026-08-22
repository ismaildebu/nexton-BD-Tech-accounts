<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Block access to a module's routes when the active company's
     * business_type doesn't include it.
     *
     * Usage: ->middleware('module:inventory') / ->middleware('module:sales-orders')
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $company = Company::find(session('company_id'));

        if ($company && ! $company->hasModule($module)) {
            abort(403, 'This module is not available for your business type.');
        }

        return $next($request);
    }
}