<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * MenuItemController manages the menu items (food and drink offerings) for a location.
 *
 * Menu items are the individual dishes and drinks available at a restaurant/bar.
 * Each item belongs to a location and optionally to a category. Items support
 * classification by type (food, drink, or both), allergen tracking, and active/new
 * status flags. This controller provides full CRUD operations with all queries
 * scoped to the authenticated user's location. An optional category_id query
 * parameter allows filtering by menu category.
 */
class MenuItemController extends Controller
{
    /**
     * List menu items for the authenticated user's location, optionally filtered by category.
     *
     * All items are scoped to the user's location_id. The related Category model
     * is eager-loaded. If a `category_id` query parameter is present, results are
     * further filtered to only that category.
     *
     * Query scoping:
     * - Always filtered by the user's location_id.
     * - Optionally filtered by ?category_id=N query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of menu items.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope to the authenticated user's location.
        $locationId = $request->user()->location_id;

        // Build the base query with category eager-loading.
        $query = MenuItem::where('location_id', $locationId)->with('category');

        // Apply optional category filter if the category_id query param is present.
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        return response()->json($query->get());
    }

    /**
     * Create a new menu item for the user's location.
     *
     * Validates the request payload and creates a new MenuItem record associated
     * with the authenticated user's location.
     *
     * Validation rules:
     * - name: required, string, max 255 chars -- the item's display name.
     * - description: optional free-text description of the item.
     * - price: optional numeric value -- the item's price.
     * - type: required, must be one of: food, drink, both -- classifies the item.
     * - category_id: optional FK to categories table for grouping.
     * - is_new: optional boolean -- flags the item as a new addition to the menu.
     * - is_active: optional boolean -- whether the item is currently on the menu.
     * - allergens: optional array -- list of allergen identifiers (e.g. ["nuts", "dairy"]).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created menu item with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',           // Display name of the menu item
            'description' => 'nullable|string',            // Detailed description
            'price' => 'nullable|numeric',                 // Price (nullable for items without fixed pricing)
            'type' => 'required|in:food,drink,both',       // Classification: food, drink, or both
            'category_id' => 'nullable|exists:categories,id', // Optional category grouping
            'is_new' => 'boolean',                         // Whether this is a new menu addition
            'is_active' => 'boolean',                      // Whether the item is currently available
            'allergens' => 'nullable|array',               // List of allergens (stored as JSON)
        ]);

        // Create the menu item, automatically associating it with the user's location.
        $menuItem = MenuItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json($menuItem, 201);
    }

    /**
     * Update an existing menu item.
     *
     * Validates the same fields as store() and applies the changes to the given
     * MenuItem model resolved via route model binding.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuItem      $menuItem  The menu item to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated menu item.
     */
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

        // Apply validated changes to the menu item record.
        $menuItem->update($validated);

        return response()->json($menuItem);
    }

    /**
     * Delete a menu item permanently.
     *
     * Uses route model binding to resolve the MenuItem instance, then deletes it.
     * Related records (86'd items, specials, push items referencing this menu item)
     * should be handled by database-level cascade rules or set-null constraints.
     *
     * @param  \App\Models\MenuItem  $menuItem  The menu item to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(MenuItem $menuItem): Response
    {
        // Permanently delete the menu item record from the database.
        $menuItem->delete();

        return response()->noContent();
    }
}
