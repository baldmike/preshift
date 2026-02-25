<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchLocationRequest;
use App\Http\Resources\UserResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * SwitchLocationController handles switching a user's active establishment.
 *
 * When a user belongs to multiple locations (via the location_user pivot),
 * they can switch their active context by calling POST /api/switch-location.
 * This updates the user's location_id and role to match their membership
 * at the target location, so all existing controllers and middleware
 * continue to work without changes.
 */
class SwitchLocationController extends Controller
{
    /**
     * Switch the authenticated user's active location.
     *
     * Validates that the user has a membership at the requested location,
     * then updates their active context (location_id + role).
     *
     * @param  \App\Http\Requests\SwitchLocationRequest  $request
     * @return \Illuminate\Http\JsonResponse  The updated user with location and membership list.
     */
    public function switch(SwitchLocationRequest $request): JsonResponse
    {
        $user = $request->user();
        $location = Location::findOrFail($request->validated('location_id'));

        // SuperAdmins can switch to any location
        if ($user->isSuperAdmin()) {
            // Ensure pivot row exists for the superadmin at this location
            if (!$user->locations()->where('location_id', $location->id)->exists()) {
                $user->locations()->attach($location->id, ['role' => 'admin']);
            }
            $user->switchLocation($location);
            $user->load(['location', 'organization']);

            return response()->json([
                'user'      => new UserResource($user),
                'locations' => Location::all()->map(fn ($loc) => [
                    'id'   => $loc->id,
                    'name' => $loc->name,
                    'role' => $user->locations()->where('location_id', $loc->id)->first()?->pivot->role ?? 'admin',
                ]),
            ]);
        }

        // Admin/Manager: allow switching to any location in their org
        if ($user->isAdmin() && $user->organization_id) {
            $orgLocation = Location::where('organization_id', $user->organization_id)
                ->where('id', $location->id)
                ->first();

            if (!$orgLocation) {
                return response()->json([
                    'message' => 'You do not have access to this location.',
                ], 403);
            }

            // Ensure pivot row exists for the admin at this org location
            if (!$user->locations()->where('location_id', $location->id)->exists()) {
                $user->locations()->attach($location->id, ['role' => 'admin']);
            }
            $user->switchLocation($location);
            $user->load(['location', 'organization']);

            return response()->json([
                'user'      => new UserResource($user),
                'locations' => Location::where('organization_id', $user->organization_id)->get()->map(fn ($loc) => [
                    'id'   => $loc->id,
                    'name' => $loc->name,
                    'role' => $user->locations()->where('location_id', $loc->id)->first()?->pivot->role ?? $user->role,
                ]),
            ]);
        }

        // Staff: require pivot membership
        $membership = $user->locations()->where('location_id', $location->id)->first();

        if (!$membership) {
            return response()->json([
                'message' => 'You do not have access to this location.',
            ], 403);
        }

        $user->switchLocation($location);
        $user->load(['location', 'organization']);

        return response()->json([
            'user'      => new UserResource($user),
            'locations' => $user->locations()->get()->map(fn ($loc) => [
                'id'   => $loc->id,
                'name' => $loc->name,
                'role' => $loc->pivot->role,
            ]),
        ]);
    }
}
