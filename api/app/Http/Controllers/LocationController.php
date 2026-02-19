<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Location::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
        ]);

        $location = Location::create($validated);

        return response()->json($location, 201);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
        ]);

        $location->update($validated);

        return response()->json($location);
    }
}
