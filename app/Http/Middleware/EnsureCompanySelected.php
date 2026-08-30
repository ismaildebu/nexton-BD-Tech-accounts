<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
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
            if ((int) session('company_id') !== (int) $user->company_id) {
                session([
                    'company_id' => $user->company_id,
                ]);
            }

            return $next($request);
        }

        /*
         * Super Admin requires an active company context.
         */
        if ($user->isSuperAdmin() && ! session()->has('company_id')) {
            return redirect()
                ->route('companies.index')
                ->with(
                    'error',
                    'Please select a company to continue.'
                );
        }

        return $next($request);
    }
}