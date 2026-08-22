<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // A company-scoped user (anyone with a company_id, i.e. not Super
        // Admin) never needs to pick — silently lock the session to their
        // own company so they can't end up inside someone else's data.
        if ($user !== null && $user->company_id !== null) {
            if (session('company_id') !== $user->company_id) {
                session(['company_id' => $user->company_id]);
            }
            return $next($request);
        }

        if (! session()->has('company_id')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No company selected.',
                ], 403);
            }

            return redirect()
                ->route('companies.index')
                ->with('error', 'Please select a company to continue.');
        }

        return $next($request);
    }
}