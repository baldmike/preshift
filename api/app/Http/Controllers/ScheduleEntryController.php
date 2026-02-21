<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleEntryRequest;
use App\Http\Requests\UpdateScheduleEntryRequest;
use App\Http\Resources\ScheduleEntryResource;
use App\Models\ScheduleEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CRUD controller for individual schedule entries.
 *
 * Each entry assigns one staff member to one shift template on a specific
 * date within a schedule. Managers use these endpoints when building out
 * a weekly schedule — adding, moving, or removing staff from shifts.
 */
class ScheduleEntryController extends Controller
{
    /**
     * Create a new schedule entry (assign staff to a shift).
     *
     * @return \Illuminate\Http\JsonResponse  The created entry with relations, 201 status.
     */
    public function store(StoreScheduleEntryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $entry = ScheduleEntry::create($validated);
        $entry->load('user', 'shiftTemplate');

        return response()->json(new ScheduleEntryResource($entry), 201);
    }

    /**
     * Update an existing schedule entry.
     *
     * @param  ScheduleEntry $scheduleEntry  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated entry with relations.
     */
    public function update(UpdateScheduleEntryRequest $request, ScheduleEntry $scheduleEntry): JsonResponse
    {
        $this->authorize('update', $scheduleEntry);

        $validated = $request->validated();

        $scheduleEntry->update($validated);
        $scheduleEntry->load('user', 'shiftTemplate');

        return response()->json(new ScheduleEntryResource($scheduleEntry));
    }

    /**
     * Remove a schedule entry (unassign staff from a shift).
     *
     * @param  ScheduleEntry $scheduleEntry  Resolved via route model binding.
     * @return \Illuminate\Http\Response      204 No Content.
     */
    public function destroy(ScheduleEntry $scheduleEntry): Response
    {
        $this->authorize('delete', $scheduleEntry);

        $scheduleEntry->delete();

        return response()->noContent();
    }
}
