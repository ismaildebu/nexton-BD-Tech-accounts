<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only platform Super Admins.
 *
 * AdminMiddleware also lets the per-company `admin` role through, which is
 * correct for company-scoped screens but NOT for platform-wide screens
 * (subscription payment verification, cross-company user data).
 */
class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->isSuperAdmin(),
            403,
            'Unauthorized - Super Admin only'
        );

        return $next($request);
    }
}
