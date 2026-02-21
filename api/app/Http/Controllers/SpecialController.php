<?php

namespace App\Http\Controllers;

use App\Events\SpecialCreated;
use App\Events\SpecialDeleted;
use App\Events\SpecialLowStock;
use App\Events\SpecialUpdated;
use App\Http\Requests\StoreSpecialRequest;
use App\Http\Resources\SpecialResource;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of current specials.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $specials = Special::where('location_id', $locationId)
            ->current()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json(SpecialResource::collection($specials));
    }

    /**
     * Create a new menu special.
     *
     * @param  \App\Http\Requests\StoreSpecialRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created special with a 201 status.
     */
    public function store(StoreSpecialRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $special = Special::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        broadcast(new SpecialCreated($special))->toOthers();

        return response()->json(new SpecialResource($special), 201);
    }

    /**
     * Update an existing special.
     *
     * @param  \App\Http\Requests\StoreSpecialRequest  $request
     * @param  \App\Models\Special       $special  The special to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated special.
     */
    public function update(StoreSpecialRequest $request, Special $special): JsonResponse
    {
        $this->authorize('update', $special);

        $validated = $request->validated();

        $special->update($validated);

        broadcast(new SpecialUpdated($special))->toOthers();

        return response()->json(new SpecialResource($special));
    }

    /**
     * Decrement a special's quantity by 1.
     *
     * @param  \App\Models\Special  $special  The special to decrement (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated special with the decremented quantity.
     */
    public function decrement(Special $special): JsonResponse
    {
        $this->authorize('decrement', $special);

        if ($special->quantity === null || $special->quantity <= 0) {
            return response()->json(['message' => 'Cannot decrement quantity.'], 422);
        }

        $special->decrement('quantity');
        $special->refresh();

        broadcast(new SpecialUpdated($special))->toOthers();

        if ($special->quantity === 2) {
            broadcast(new SpecialLowStock($special));
        }

        return response()->json(new SpecialResource($special));
    }

    /**
     * Delete a special permanently.
     *
     * @param  \App\Models\Special  $special  The special to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Special $special): Response
    {
        $this->authorize('delete', $special);

        $data = ['id' => $special->id, 'location_id' => $special->location_id];

        $special->delete();

        broadcast(new SpecialDeleted($data))->toOthers();

        return response()->noContent();
    }
}
