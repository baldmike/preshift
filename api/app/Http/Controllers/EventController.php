<?php

namespace App\Http\Controllers;

use App\Events\EventCreated;
use App\Events\EventDeleted;
use App\Events\EventUpdated;
use App\Http\Requests\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * EventController manages daily events for a location.
 */
class EventController extends Controller
{
    /**
     * List events for the authenticated user's location, filtered by date.
     *
     * Defaults to today's events; pass `?date=YYYY-MM-DD` to query a specific day.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of events for the given date.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locationId = $user->location_id;
        $date = $request->query('date', now()->toDateString());

        $events = Event::where('location_id', $locationId)
            ->forDate($date)
            ->with('creator')
            ->get();

        return response()->json(EventResource::collection($events));
    }

    /**
     * Create a new event for the user's location.
     *
     * @param  \App\Http\Requests\StoreEventRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created event with a 201 status.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $event = Event::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        broadcast(new EventCreated($event))->toOthers();

        return response()->json(new EventResource($event), 201);
    }

    /**
     * Update an existing event.
     *
     * @param  \App\Http\Requests\StoreEventRequest  $request
     * @param  \App\Models\Event                     $event  The event to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated event.
     */
    public function update(StoreEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validated();

        $event->update($validated);

        broadcast(new EventUpdated($event))->toOthers();

        return response()->json(new EventResource($event));
    }

    /**
     * Delete an event permanently.
     *
     * @param  \App\Models\Event  $event  The event to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Event $event): Response
    {
        $this->authorize('delete', $event);

        $data = ['id' => $event->id, 'location_id' => $event->location_id];

        $event->delete();

        broadcast(new EventDeleted($data))->toOthers();

        return response()->noContent();
    }
}
