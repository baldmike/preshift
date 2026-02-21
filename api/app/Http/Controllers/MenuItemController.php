<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * MenuItemController manages the menu items (food and drink offerings) for a location.
 */
class MenuItemController extends Controller
{
    /**
     * List menu items for the authenticated user's location, optionally filtered by category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of menu items.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $query = MenuItem::where('location_id', $locationId)->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        return response()->json(MenuItemResource::collection($query->get()));
    }

    /**
     * Create a new menu item for the user's location.
     *
     * @param  \App\Http\Requests\StoreMenuItemRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created menu item with a 201 status.
     */
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $menuItem = MenuItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json(new MenuItemResource($menuItem), 201);
    }

    /**
     * Update an existing menu item.
     *
     * @param  \App\Http\Requests\StoreMenuItemRequest  $request
     * @param  \App\Models\MenuItem      $menuItem  The menu item to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated menu item.
     */
    public function update(StoreMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorize('update', $menuItem);

        $validated = $request->validated();

        $menuItem->update($validated);

        return response()->json(new MenuItemResource($menuItem));
    }

    /**
     * Delete a menu item permanently.
     *
     * @param  \App\Models\MenuItem  $menuItem  The menu item to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(MenuItem $menuItem): Response
    {
        $this->authorize('delete', $menuItem);

        $menuItem->delete();

        return response()->noContent();
    }
}
