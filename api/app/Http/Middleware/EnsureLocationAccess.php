<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLocationAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admins can access without location_id
        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->location_id) {
            return response()->json(['message' => 'No location assigned.'], 403);
        }

        return $next($request);
    }
}
