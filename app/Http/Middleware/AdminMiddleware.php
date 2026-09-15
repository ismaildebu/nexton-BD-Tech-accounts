<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // For testing: Allow user ID 1 (super admin)
        if (auth()->check() && auth()->id() === 1) {
            return $next($request);
        }

        // Check if user has admin role
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }

        // Deny access
        abort(403, 'Unauthorized - Admin only');
    }
}