<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocationController manages restaurant/bar location records.
 */
class LocationController extends Controller
{
    /**
     * List locations scoped by the user's role and organization.
     *
     * - SuperAdmin: all locations across all orgs
     * - Admin/Manager: all locations in their organization
     * - Staff: only their pivot locations
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of locations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return response()->json(LocationResource::collection(Location::all()));
        }

        if ($user->isAdmin() && $user->organization_id) {
            $locations = Location::where('organization_id', $user->organization_id)->get();
            return response()->json(LocationResource::collection($locations));
        }

        $locations = $user->locations()->get();
        return response()->json(LocationResource::collection($locations));
    }

    /**
     * Create a new location.
     *
     * @param  \App\Http\Requests\StoreLocationRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created location with a 201 status.
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $location = Location::create($validated);

        return response()->json(new LocationResource($location), 201);
    }

    /**
     * Update an existing location.
     *
     * @param  \App\Http\Requests\StoreLocationRequest  $request
     * @param  \App\Models\Location      $location  The location to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated location.
     */
    public function update(StoreLocationRequest $request, Location $location): JsonResponse
    {
        $validated = $request->validated();

        $location->update($validated);

        // Re-geocode if city or state changed
        if (isset($validated['city']) || isset($validated['state'])) {
            $location->geocodeFromCityState();
        }

        return response()->json(new LocationResource($location));
    }
}
