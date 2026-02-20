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
    /**
     * Handle an incoming request by verifying the user has the SuperAdmin flag.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @param  \Closure                  $next     The next middleware or controller in the pipeline.
     * @return mixed  The response from the next handler, or a 403 JSON error response.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->is_superadmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
