<?php

namespace App\Http\Controllers;

use App\Events\ItemEightySixed;
use App\Events\ItemRestored;
use App\Http\Requests\StoreEightySixedRequest;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of active 86'd items.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $items = EightySixed::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'user')
            ->get();

        return response()->json($items);
    }

    /**
     * Mark a new item as 86'd (unavailable).
     *
     * @param  \App\Http\Requests\StoreEightySixedRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created 86'd item with a 201 status.
     */
    public function store(StoreEightySixedRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $item = EightySixed::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'eighty_sixed_by' => $request->user()->id,
        ]);

        broadcast(new ItemEightySixed($item))->toOthers();

        return response()->json($item, 201);
    }

    /**
     * Restore a previously 86'd item, marking it as available again.
     *
     * @param  \App\Models\EightySixed  $eightySixed  The 86'd item to restore (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated 86'd item with the restored_at timestamp.
     */
    public function restore(EightySixed $eightySixed): JsonResponse
    {
        $eightySixed->update(['restored_at' => now()]);

        broadcast(new ItemRestored($eightySixed))->toOthers();

        return response()->json($eightySixed);
    }
}
