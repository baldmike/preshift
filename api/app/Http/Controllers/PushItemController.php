<?php

namespace App\Http\Controllers;

use App\Events\PushItemCreated;
use App\Events\PushItemDeleted;
use App\Events\PushItemUpdated;
use App\Http\Requests\StorePushItemRequest;
use App\Http\Resources\PushItemResource;
use App\Models\PushItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PushItemController manages "push items" -- menu items that staff should
 * actively recommend or upsell to guests.
 */
class PushItemController extends Controller
{
    /**
     * List all active push items for the authenticated user's location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of active push items.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $pushItems = PushItem::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json(PushItemResource::collection($pushItems));
    }

    /**
     * Create a new push item for the user's location.
     *
     * @param  \App\Http\Requests\StorePushItemRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created push item with a 201 status.
     */
    public function store(StorePushItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pushItem = PushItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        broadcast(new PushItemCreated($pushItem))->toOthers();

        return response()->json(new PushItemResource($pushItem), 201);
    }

    /**
     * Update an existing push item.
     *
     * @param  \App\Http\Requests\StorePushItemRequest  $request
     * @param  \App\Models\PushItem      $pushItem  The push item to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated push item.
     */
    public function update(StorePushItemRequest $request, PushItem $pushItem): JsonResponse
    {
        $this->authorize('update', $pushItem);

        $validated = $request->validated();

        $pushItem->update($validated);

        broadcast(new PushItemUpdated($pushItem))->toOthers();

        return response()->json(new PushItemResource($pushItem));
    }

    /**
     * Delete a push item permanently.
     *
     * @param  \App\Models\PushItem  $pushItem  The push item to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(PushItem $pushItem): Response
    {
        $this->authorize('delete', $pushItem);

        $data = ['id' => $pushItem->id, 'location_id' => $pushItem->location_id];

        $pushItem->delete();

        broadcast(new PushItemDeleted($data))->toOthers();

        return response()->noContent();
    }
}
