<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocationController manages restaurant/bar location records.
 *
 * Locations are the top-level organizational unit in the application. Each
 * location represents a physical restaurant or bar, and most other resources
 * (menu items, specials, announcements, etc.) are scoped to a specific location.
 * This controller provides list, create, and update operations. It is typically
 * restricted to admin users via middleware defined in the route configuration.
 */
class LocationController extends Controller
{
    /**
     * List all locations in the system.
     *
     * Returns every location record without any filtering or scoping.
     * This endpoint is typically restricted to admin users who need
     * to manage or view all locations.
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
     * Validates the request and creates a new location record in the database.
     *
     * Validation rules:
     * - name: required, string, max 255 chars -- the display name of the location.
     * - address: optional, string, max 255 chars -- the physical street address.
     * - timezone: optional, string, max 255 chars -- the IANA timezone (e.g. "America/New_York").
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created location with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',     // Display name for the location
            'address' => 'nullable|string|max:255',  // Physical street address (optional)
            'timezone' => 'nullable|string|max:255', // IANA timezone identifier (optional)
        ]);

        // Create the location record with validated data.
        $location = Location::create($validated);

        return response()->json($location, 201);
    }

    /**
     * Update an existing location.
     *
     * Validates the same fields as store() and applies the changes to the given
     * Location model resolved via route model binding.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location      $location  The location to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated location.
     */
    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
        ]);

        // Apply validated changes to the location record.
        $location->update($validated);

        return response()->json($location);
    }
}
