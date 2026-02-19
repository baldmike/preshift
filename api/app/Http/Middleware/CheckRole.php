<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * CheckRole middleware restricts access to routes based on user roles.
 *
 * This middleware accepts one or more role names as parameters and verifies
 * that the authenticated user's role matches at least one of them. It is
 * applied to routes via the route definition, e.g.:
 *
 *     Route::middleware('role:admin,manager')->group(...)
 *
 * Authorization logic:
 * - If there is no authenticated user, or the user's role is not in the
 *   allowed roles list, a 403 Forbidden response is returned.
 * - If the user's role matches any of the provided role parameters,
 *   the request proceeds to the next middleware or controller.
 *
 * The variadic `...$roles` parameter allows specifying multiple allowed roles
 * as comma-separated values in the route middleware declaration.
 */
class CheckRole
{
    /**
     * Handle an incoming request by verifying the user has an allowed role.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @param  \Closure                  $next     The next middleware or controller in the pipeline.
     * @param  string                    ...$roles One or more role names that are permitted access.
     * @return mixed  The response from the next handler, or a 403 JSON error response.
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        // Reject the request if there is no authenticated user or if the user's
        // role is not found within the list of allowed roles for this route.
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // User has an allowed role; proceed to the next handler.
        return $next($request);
    }
}
