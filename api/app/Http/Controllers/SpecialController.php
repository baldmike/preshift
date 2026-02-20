<?php

namespace App\Http\Controllers;

use App\Events\SpecialCreated;
use App\Events\SpecialDeleted;
use App\Events\SpecialLowStock;
use App\Events\SpecialUpdated;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SpecialController manages menu specials (promotions) for a location.
 *
 * Specials represent temporary menu promotions such as daily happy-hour deals,
 * weekly features, monthly highlights, or limited-time offerings. This controller
 * provides full CRUD operations, with all records scoped to the authenticated
 * user's location. Real-time WebSocket broadcasting notifies other connected
 * clients whenever specials are created, updated, or deleted.
 */
class SpecialController extends Controller
{
    /**
     * List all current specials for the authenticated user's location.
     *
     * Uses the `current()` scope on the Special model to return only specials
     * whose date range includes the present time. Eager-loads the related
     * MenuItem and the User who created the special.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of current specials.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope to the authenticated user's location.
        $locationId = $request->user()->location_id;

        // Retrieve only current (time-relevant) specials with their relationships.
        $specials = Special::where('location_id', $locationId)
            ->current()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json($specials);
    }

    /**
     * Create a new menu special.
     *
     * Validates the request payload, creates the special record associated with the
     * user's location, and broadcasts a SpecialCreated event for real-time updates.
     *
     * Validation rules:
     * - title: required, string, max 255 chars -- the special's display name.
     * - description: optional free-text description of the special.
     * - type: required, must be one of: daily, weekly, monthly, limited_time.
     * - starts_at: required, must be a valid date -- when the special begins.
     * - ends_at: optional date -- when the special expires (null = open-ended).
     * - menu_item_id: optional FK to menu_items table for linking to a specific dish/drink.
     * - is_active: optional boolean flag to enable/disable the special.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created special with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',                   // Display title of the special
            'description' => 'nullable|string',                     // Detailed description
            'type' => 'required|in:daily,weekly,monthly,limited_time', // Recurrence/duration type
            'starts_at' => 'required|date',                         // When the special becomes active
            'ends_at' => 'nullable|date',                           // When the special expires (optional)
            'menu_item_id' => 'nullable|exists:menu_items,id',     // Optional link to a specific menu item
            'is_active' => 'boolean',                               // Whether the special is enabled
            'quantity' => 'nullable|integer|min:0',                    // Limited quantity; null = unlimited
        ]);

        // Create the special, associating it with the user's location and recording the creator.
        $special = Special::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        // Broadcast SpecialCreated event via WebSocket to all other connected clients.
        broadcast(new SpecialCreated($special))->toOthers();

        return response()->json($special, 201);
    }

    /**
     * Update an existing special.
     *
     * Validates the same fields as store(), applies the changes to the given
     * Special model (resolved via route model binding), and broadcasts a
     * SpecialUpdated event for real-time client synchronization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Special       $special  The special to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated special.
     */
    public function update(Request $request, Special $special): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,weekly,monthly,limited_time',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
            'quantity' => 'nullable|integer|min:0',
        ]);

        // Apply validated changes to the special record.
        $special->update($validated);

        // Broadcast SpecialUpdated event to notify other clients of the change.
        broadcast(new SpecialUpdated($special))->toOthers();

        return response()->json($special);
    }

    /**
     * Decrement a special's quantity by 1.
     *
     * Guards against going below 0. Broadcasts SpecialUpdated so all clients
     * see the new quantity, and fires SpecialLowStock when quantity hits 2.
     */
    public function decrement(Special $special): JsonResponse
    {
        if ($special->quantity === null || $special->quantity <= 0) {
            return response()->json(['message' => 'Cannot decrement quantity.'], 422);
        }

        $special->decrement('quantity');
        $special->refresh();

        broadcast(new SpecialUpdated($special))->toOthers();

        if ($special->quantity === 2) {
            broadcast(new SpecialLowStock($special));
        }

        return response()->json($special);
    }

    /**
     * Delete a special permanently.
     *
     * Captures the special's ID and location_id before deletion so they can be
     * included in the SpecialDeleted broadcast event, allowing clients to remove
     * the correct item from their local state.
     *
     * @param  \App\Models\Special  $special  The special to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Special $special): Response
    {
        // Capture identifiers before deletion for the broadcast payload.
        $data = ['id' => $special->id, 'location_id' => $special->location_id];

        // Permanently delete the special record from the database.
        $special->delete();

        // Broadcast SpecialDeleted event so other clients can remove the item from their UI.
        broadcast(new SpecialDeleted($data))->toOthers();

        return response()->noContent();
    }
}
