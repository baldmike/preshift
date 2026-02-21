<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * LocationController manages restaurant/bar location records.
 */
class LocationController extends Controller
{
    /**
     * List all locations in the system.
     *
     * @return \Illuminate\Http\JsonResponse  A JSON array of all locations.
     */
    public function index(): JsonResponse
    {
        return response()->json(Location::all());
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

        return response()->json($location, 201);
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

        return response()->json($location);
    }
}
