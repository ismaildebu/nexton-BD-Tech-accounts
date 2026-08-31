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
         * Company-scoped users are permanently locked to their assigned
         * company. Any session company context is overwritten.
         */
        if ($user->company_id !== null) {
            if ((int) session('company_id') !== (int) $user->company_id) {
                session()->put([
                    'company_id' => $user->company_id,
                ]);
            }

            return $next($request);
        }

        /*
         * Super Admin is not permanently attached to one company.
         * A company must be selected before entering company-scoped
         * application areas.
         *
         * Company management routes must remain accessible without a
         * selected company, so the controller can display the company list.
         */
        if (
            $user->isSuperAdmin()
            && ! session()->has('company_id')
            && ! $request->routeIs(
                'companies.index',
                'companies.create',
                'companies.store'
            )
        ) {
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