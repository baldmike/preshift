<?php

namespace App\Http\Controllers;

use App\Events\PushItemCreated;
use App\Events\PushItemDeleted;
use App\Events\PushItemUpdated;
use App\Models\PushItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PushItemController manages "push items" -- menu items that staff should
 * actively recommend or upsell to guests.
 *
 * Push items are used by management to highlight specific dishes or drinks
 * that need to be promoted (e.g., items close to expiration, high-margin
 * items, or new additions). This controller provides full CRUD operations
 * scoped to the authenticated user's location, with real-time WebSocket
 * broadcasting for all write operations.
 */
class PushItemController extends Controller
{
    /**
     * List all active push items for the authenticated user's location.
     *
     * Uses the `active()` scope on the PushItem model to return only items
     * that are currently flagged as active. Eager-loads the associated
     * MenuItem and the User who created the push item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of active push items.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope to the authenticated user's location.
        $locationId = $request->user()->location_id;

        // Retrieve only active push items with their relationships.
        $pushItems = PushItem::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json($pushItems);
    }

    /**
     * Create a new push item for the user's location.
     *
     * Validates the request, creates the push item record, and broadcasts
     * a PushItemCreated event for real-time client updates.
     *
     * Validation rules:
     * - title: required, string, max 255 chars -- the push item's display name.
     * - description: optional free-text description of the push item.
     * - reason: optional string explaining why the item is being pushed (e.g. "overstock").
     * - priority: required, must be one of: low, medium, high -- urgency of the push.
     * - menu_item_id: optional FK to menu_items table for linking to a specific dish/drink.
     * - is_active: optional boolean flag to enable/disable the push item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created push item with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',              // Display title for the push item
            'description' => 'nullable|string',                // Optional detailed description
            'reason' => 'nullable|string',                     // Why management wants this item pushed
            'priority' => 'required|in:low,medium,high',       // Urgency level for staff
            'menu_item_id' => 'nullable|exists:menu_items,id', // Optional link to a specific menu item
            'is_active' => 'boolean',                          // Whether the push item is currently active
        ]);

        // Create the push item, associating it with the user's location and recording the creator.
        $pushItem = PushItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        // Broadcast PushItemCreated event via WebSocket to all other connected clients.
        broadcast(new PushItemCreated($pushItem))->toOthers();

        return response()->json($pushItem, 201);
    }

    /**
     * Update an existing push item.
     *
     * Validates the same fields as store(), applies the changes to the given
     * PushItem model (resolved via route model binding), and broadcasts a
     * PushItemUpdated event for real-time synchronization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PushItem      $pushItem  The push item to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated push item.
     */
    public function update(Request $request, PushItem $pushItem): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ]);

        // Apply validated changes to the push item record.
        $pushItem->update($validated);

        // Broadcast PushItemUpdated event to notify other clients of the change.
        broadcast(new PushItemUpdated($pushItem))->toOthers();

        return response()->json($pushItem);
    }

    /**
     * Delete a push item permanently.
     *
     * Captures the push item's ID and location_id before deletion so they can be
     * included in the PushItemDeleted broadcast event, allowing clients to remove
     * the correct item from their local state.
     *
     * @param  \App\Models\PushItem  $pushItem  The push item to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(PushItem $pushItem): Response
    {
        // Capture identifiers before deletion for the broadcast payload.
        $data = ['id' => $pushItem->id, 'location_id' => $pushItem->location_id];

        // Permanently delete the push item record from the database.
        $pushItem->delete();

        // Broadcast PushItemDeleted event so other clients can remove the item from their UI.
        broadcast(new PushItemDeleted($data))->toOthers();

        return response()->noContent();
    }
}
