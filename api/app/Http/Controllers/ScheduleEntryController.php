<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Validation:
     *   - schedule_id: required, must exist in schedules table.
     *   - user_id: required, must exist in users table.
     *   - shift_template_id: required, must exist in shift_templates table.
     *   - date: required, valid date — the specific calendar day.
     *   - role: required, must be "server" or "bartender".
     *   - notes: optional, max 255 chars — manager notes (e.g. "training").
     *
     * @return \Illuminate\Http\JsonResponse  The created entry with relations, 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id'       => 'required|exists:schedules,id',
            'user_id'           => 'required|exists:users,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date'              => 'required|date',
            'role'              => 'required|in:server,bartender',
            'notes'             => 'nullable|string|max:255',
        ]);

        $entry = ScheduleEntry::create($validated);
        $entry->load('user', 'shiftTemplate');

        return response()->json($entry, 201);
    }

    /**
     * Update an existing schedule entry.
     *
     * Allows the manager to change the assigned user, shift template, date,
     * role, or notes for an existing entry.
     *
     * @param  ScheduleEntry $scheduleEntry  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated entry with relations.
     */
    public function update(Request $request, ScheduleEntry $scheduleEntry): JsonResponse
    {
        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date'              => 'required|date',
            'role'              => 'required|in:server,bartender',
            'notes'             => 'nullable|string|max:255',
        ]);

        $scheduleEntry->update($validated);
        $scheduleEntry->load('user', 'shiftTemplate');

        return response()->json($scheduleEntry);
    }

    /**
     * Remove a schedule entry (unassign staff from a shift).
     *
     * @param  ScheduleEntry $scheduleEntry  Resolved via route model binding.
     * @return \Illuminate\Http\Response      204 No Content.
     */
    public function destroy(ScheduleEntry $scheduleEntry): Response
    {
        $scheduleEntry->delete();

        return response()->noContent();
    }
}
