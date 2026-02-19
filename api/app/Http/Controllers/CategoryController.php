<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CategoryController manages menu categories for a location.
 *
 * Categories are used to organize menu items into logical groups (e.g., "Appetizers",
 * "Entrees", "Cocktails", "Desserts"). Each category belongs to a specific location
 * and has an optional sort_order field for controlling display sequence. This controller
 * provides full CRUD operations with all queries scoped to the authenticated user's
 * location.
 */
class CategoryController extends Controller
{
    /**
     * List all categories for the authenticated user's location, ordered by sort_order.
     *
     * Results are scoped to the user's location_id and sorted ascending by
     * the sort_order column to maintain consistent display ordering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of categories in sort order.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope to the authenticated user's location.
        $locationId = $request->user()->location_id;

        // Retrieve categories sorted by their display order.
        $categories = Category::where('location_id', $locationId)
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    /**
     * Create a new menu category for the user's location.
     *
     * Validates the request and creates a new category record associated with
     * the authenticated user's location.
     *
     * Validation rules:
     * - name: required, string, max 255 chars -- the category's display name.
     * - sort_order: optional integer -- controls the display order (lower numbers appear first).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created category with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',  // Display name for the category
            'sort_order' => 'nullable|integer',   // Position in the display order (lower = first)
        ]);

        // Create the category, automatically associating it with the user's location.
        $category = Category::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     *
     * Validates the same fields as store() and applies the changes to the given
     * Category model resolved via route model binding.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category      $category  The category to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        // Apply validated changes to the category record.
        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Delete a category permanently.
     *
     * Uses route model binding to resolve the Category instance, then deletes it.
     * Menu items that reference this category should have their category_id set to
     * null via database-level set-null constraints, or be reassigned beforehand.
     *
     * @param  \App\Models\Category  $category  The category to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Category $category): Response
    {
        // Permanently delete the category record from the database.
        $category->delete();

        return response()->noContent();
    }
}
