<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetupLocationRequest;
use App\Http\Resources\UserResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * SetupController handles the initial establishment creation flow.
 *
 * When a brand-new admin logs in for the first time and has no location
 * memberships, they are directed to the setup flow. This controller
 * creates their first establishment, assigns them as admin via the
 * location_user pivot, and updates their active context.
 */
class SetupController extends Controller
{
    /**
     * Create the admin's first establishment.
     *
     * Only accessible by admin users who have no location memberships yet.
     * Creates the Location record, triggers geocoding, creates the pivot
     * membership, and sets the user's active location_id and role.
     *
     * @param  \App\Http\Requests\SetupLocationRequest  $request
     * @return \Illuminate\Http\JsonResponse  The user with their new location and membership list.
     */
    public function store(SetupLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        // Only admins without any location memberships can use setup
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can create establishments.',
            ], 403);
        }

        if ($user->locations()->count() > 0) {
            return response()->json([
                'message' => 'You already have an establishment. Use location management instead.',
            ], 422);
        }

        $validated = $request->validated();

        // Create the new location
        $location = Location::create([
            'name'  => $validated['name'],
            'city'  => $validated['city'],
            'state' => $validated['state'],
        ]);

        // Attempt geocoding (non-blocking — fails silently)
        $location->geocodeFromCityState();

        // Create the pivot membership
        $user->locations()->attach($location->id, ['role' => 'admin']);

        // Set this as the user's active location
        $user->location_id = $location->id;
        $user->role = 'admin';
        $user->save();

        $user->load('location');

        return response()->json([
            'user'      => new UserResource($user),
            'locations' => $user->locations()->get()->map(fn ($loc) => [
                'id'   => $loc->id,
                'name' => $loc->name,
                'role' => $loc->pivot->role,
            ]),
        ], 201);
    }
}
