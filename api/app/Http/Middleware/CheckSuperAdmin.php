<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Restricts access to routes that require the SuperAdmin flag.
 * Returns 403 if the authenticated user does not have `is_superadmin = true`.
 */
class CheckSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->is_superadmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
