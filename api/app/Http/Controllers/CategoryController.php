<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CategoryController manages menu categories for a location.
 */
class CategoryController extends Controller
{
    /**
     * List all categories for the authenticated user's location, ordered by sort_order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of categories in sort order.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $categories = Category::where('location_id', $locationId)
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    /**
     * Create a new menu category for the user's location.
     *
     * @param  \App\Http\Requests\StoreCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created category with a 201 status.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = Category::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     *
     * @param  \App\Http\Requests\StoreCategoryRequest  $request
     * @param  \App\Models\Category      $category  The category to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated category.
     */
    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Delete a category permanently.
     *
     * @param  \App\Models\Category  $category  The category to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Category $category): Response
    {
        $category->delete();

        return response()->noContent();
    }
}
