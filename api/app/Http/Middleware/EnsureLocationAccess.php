<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * EnsureLocationAccess middleware verifies that the authenticated user has a
 * location assignment before allowing the request to proceed.
 *
 * Most API endpoints in this application require data to be scoped to a specific
 * location. This middleware enforces that non-admin users have a location_id set
 * on their account. Without it, location-scoped queries would fail or return
 * empty results.
 *
 * Authorization logic:
 * - Unauthenticated users: rejected with a 401 Unauthenticated response.
 * - Admin users: always allowed through, even without a location_id, because
 *   admins operate across all locations.
 * - Non-admin users without a location_id: rejected with a 403 Forbidden response,
 *   indicating they need to be assigned to a location before accessing the app.
 * - Non-admin users with a location_id: allowed through to the next middleware/controller.
 */
class EnsureLocationAccess
{
    /**
     * Handle an incoming request by verifying location access.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @param  \Closure                  $next     The next middleware or controller in the pipeline.
     * @return mixed  The response from the next handler, or a JSON error response.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Reject unauthenticated requests with a 401 status.
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admins can access any endpoint regardless of location assignment.
        // They operate at a system-wide level and are not bound to a single location.
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Non-admin users must have a location_id assigned to their account.
        // Without a location, location-scoped queries cannot function properly.
        if (!$user->location_id) {
            return response()->json(['message' => 'No location assigned.'], 403);
        }

        // User is authenticated and has a valid location assignment; proceed.
        return $next($request);
    }
}
