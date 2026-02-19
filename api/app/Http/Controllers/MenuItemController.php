<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $query = MenuItem::where('location_id', $locationId)->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'type' => 'required|in:food,drink,both',
            'category_id' => 'nullable|exists:categories,id',
            'is_new' => 'boolean',
            'is_active' => 'boolean',
            'allergens' => 'nullable|array',
        ]);

        $menuItem = MenuItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json($menuItem, 201);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'type' => 'required|in:food,drink,both',
            'category_id' => 'nullable|exists:categories,id',
            'is_new' => 'boolean',
            'is_active' => 'boolean',
            'allergens' => 'nullable|array',
        ]);

        $menuItem->update($validated);

        return response()->json($menuItem);
    }

    public function destroy(MenuItem $menuItem): Response
    {
        $menuItem->delete();

        return response()->noContent();
    }
}
