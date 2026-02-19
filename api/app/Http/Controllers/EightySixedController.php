<?php

namespace App\Http\Controllers;

use App\Events\ItemEightySixed;
use App\Events\ItemRestored;
use App\Models\EightySixed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EightySixedController manages 86'd (unavailable) menu items for a location.
 *
 * In restaurant terminology, "86'd" means an item is no longer available.
 * This controller allows staff to list currently 86'd items, mark new items
 * as 86'd, and restore items when they become available again. All data is
 * scoped to the authenticated user's location. Real-time broadcasting is used
 * to notify other connected clients when items are 86'd or restored.
 */
class EightySixedController extends Controller
{
    /**
     * List all currently 86'd (unavailable) items for the user's location.
     *
     * Uses the `active()` scope on the EightySixed model to exclude items that
     * have already been restored (i.e., where `restored_at` is null). Eager-loads
     * the related MenuItem and the User who reported the item as 86'd.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of active 86'd items.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope query to the authenticated user's location.
        $locationId = $request->user()->location_id;

        // Retrieve only active (not yet restored) 86'd items with their relationships.
        $items = EightySixed::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'user')
            ->get();

        return response()->json($items);
    }

    /**
     * Mark a new item as 86'd (unavailable).
     *
     * Validates the incoming request, creates a new EightySixed record associated
     * with the user's location, and broadcasts an ItemEightySixed event in real-time
     * so other connected clients are immediately notified of the change.
     *
     * Validation rules:
     * - item_name: required, string, max 255 chars -- the display name of the 86'd item.
     * - menu_item_id: optional, must reference an existing menu_items record if provided.
     * - reason: optional, string, max 255 chars -- why the item was 86'd (e.g. "ran out").
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created 86'd item with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',          // Display name of the unavailable item
            'menu_item_id' => 'nullable|exists:menu_items,id', // Optional FK to the menu_items table
            'reason' => 'nullable|string|max:255',             // Optional reason for 86'ing the item
        ]);

        // Create the 86'd item record, automatically associating it with the
        // authenticated user's location and recording who reported it.
        $item = EightySixed::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'eighty_sixed_by' => $request->user()->id,
        ]);

        // Broadcast the ItemEightySixed event to all other connected clients
        // via WebSocket. The toOthers() call excludes the requesting user.
        broadcast(new ItemEightySixed($item))->toOthers();

        return response()->json($item, 201);
    }

    /**
     * Restore a previously 86'd item, marking it as available again.
     *
     * Sets the `restored_at` timestamp to the current time, which causes the
     * item to be excluded from the `active()` scope. Broadcasts an ItemRestored
     * event so other clients can update their UI in real-time.
     *
     * Uses route model binding to resolve the EightySixed instance from the URL.
     *
     * @param  \App\Models\EightySixed  $eightySixed  The 86'd item to restore (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated 86'd item with the restored_at timestamp.
     */
    public function restore(EightySixed $eightySixed): JsonResponse
    {
        // Set the restored_at timestamp to mark this item as no longer 86'd.
        $eightySixed->update(['restored_at' => now()]);

        // Broadcast the ItemRestored event to notify other connected clients.
        broadcast(new ItemRestored($eightySixed))->toOthers();

        return response()->json($eightySixed);
    }
}
