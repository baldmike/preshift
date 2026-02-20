<?php

namespace App\Http\Controllers;

use App\Events\ShiftDropFilled;
use App\Events\ShiftDropRequested;
use App\Events\ShiftDropVolunteered;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShiftDropController manages the shift-drop workflow for a location.
 *
 * A "shift drop" allows a staff member to release an assigned shift so that
 * another qualified employee can volunteer to pick it up. The lifecycle is:
 *   1. Staff member creates a drop (status = open) via store().
 *   2. Other staff with the same role volunteer via volunteer().
 *   3. A manager selects a volunteer via select(), which reassigns the
 *      schedule entry and marks the drop as filled.
 *   4. The original requester may cancel an open drop via cancel().
 *
 * Real-time WebSocket events (ShiftDropRequested, ShiftDropVolunteered,
 * ShiftDropFilled) are broadcast on the location's private channel so all
 * connected clients stay in sync.
 */
class ShiftDropController extends Controller
{
    /**
     * List shift drops visible to the authenticated user.
     *
     * Authorization logic:
     * - Staff users see their own drops (any status) plus open drops that match
     *   their role, allowing them to discover shifts they could volunteer for.
     * - Managers see all drops for the location regardless of role or status.
     *
     * Results are eager-loaded with the schedule entry, shift template, requester,
     * filler, and volunteers to avoid N+1 queries on the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of shift drop records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ShiftDrop::whereHas('scheduleEntry.schedule', function ($q) use ($user) {
            $q->where('location_id', $user->location_id);
        })->with('scheduleEntry.shiftTemplate', 'scheduleEntry.user', 'requester', 'filler', 'volunteers.user');

        if ($user->isStaff()) {
            $query->where(function ($q) use ($user) {
                // Own drops (any status)
                $q->where('requested_by', $user->id)
                  // Open drops for the same role
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('status', 'open')
                         ->whereHas('scheduleEntry', function ($q3) use ($user) {
                             $q3->where('role', $user->role);
                         });
                  });
            });
        }

        $drops = $query->orderByDesc('created_at')->get();

        return response()->json($drops);
    }

    /**
     * Create a new shift drop request (staff action -- can only drop own shifts).
     *
     * Validates the request payload, confirms the authenticated user owns the
     * schedule entry, then creates a ShiftDrop record with status "open" and
     * broadcasts a ShiftDropRequested event to the location's private channel.
     *
     * Validation rules:
     * - schedule_entry_id: required, must reference an existing schedule entry.
     * - reason: optional, string, max 255 chars -- why the shift is being dropped.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created shift drop with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_entry_id' => 'required|exists:schedule_entries,id',
            'reason'            => 'nullable|string|max:255',
        ]);

        $entry = ScheduleEntry::findOrFail($validated['schedule_entry_id']);
        if ($entry->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only drop your own shifts.'], 403);
        }

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $validated['schedule_entry_id'],
            'requested_by'      => $request->user()->id,
            'reason'            => $validated['reason'] ?? null,
            'status'            => 'open',
        ]);

        $drop->load('scheduleEntry.shiftTemplate', 'requester', 'volunteers.user');

        broadcast(new ShiftDropRequested($drop))->toOthers();

        return response()->json($drop, 201);
    }

    /**
     * Volunteer to pick up an open shift drop.
     *
     * A staff member indicates willingness to cover the dropped shift. The method
     * enforces several guard clauses before recording the volunteer:
     * - The drop must still be in "open" status.
     * - The authenticated user cannot volunteer for their own drop.
     * - The user's role must match the schedule entry's role.
     * - Duplicate volunteers are rejected.
     *
     * On success a ShiftDropVolunteered event is broadcast to the location channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to volunteer for (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with volunteers loaded.
     */
    public function volunteer(Request $request, ShiftDrop $shiftDrop): JsonResponse
    {
        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This shift drop is no longer available.'], 422);
        }

        if ($shiftDrop->requested_by === $request->user()->id) {
            return response()->json(['message' => 'You cannot volunteer for your own drop.'], 422);
        }

        // Check same role
        $entryRole = $shiftDrop->scheduleEntry->role;
        if ($request->user()->role !== $entryRole) {
            return response()->json(['message' => 'You must have the same role to pick up this shift.'], 422);
        }

        // Prevent duplicate volunteers
        $exists = $shiftDrop->volunteers()->where('user_id', $request->user()->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'You have already volunteered for this drop.'], 422);
        }

        $shiftDrop->volunteers()->create([
            'user_id'  => $request->user()->id,
            'selected' => false,
        ]);

        $shiftDrop->load('scheduleEntry.shiftTemplate', 'requester', 'volunteers.user');

        broadcast(new ShiftDropVolunteered($shiftDrop))->toOthers();

        return response()->json($shiftDrop);
    }

    /**
     * Select a volunteer to fill the shift drop (manager action).
     *
     * The manager chooses one of the existing volunteers to take over the shift.
     * This method verifies the drop is still open and that the specified user has
     * actually volunteered, then performs three atomic updates:
     *   1. Marks the volunteer record as selected.
     *   2. Reassigns the underlying schedule entry to the selected user.
     *   3. Updates the drop's status to "filled" with the filler ID and timestamp.
     *
     * A ShiftDropFilled event is broadcast to notify all connected clients.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to fill (via route model binding).
     * @param  \App\Models\User          $user       The volunteer to assign the shift to (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with filler and volunteers loaded.
     */
    public function select(Request $request, ShiftDrop $shiftDrop, User $user): JsonResponse
    {
        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This shift drop is no longer available.'], 422);
        }

        // Verify the user is actually a volunteer
        $volunteer = $shiftDrop->volunteers()->where('user_id', $user->id)->first();
        if (! $volunteer) {
            return response()->json(['message' => 'This user has not volunteered for this drop.'], 422);
        }

        // Mark volunteer as selected
        $volunteer->update(['selected' => true]);

        // Reassign the schedule entry
        $shiftDrop->scheduleEntry->update(['user_id' => $user->id]);

        // Update the drop
        $shiftDrop->update([
            'status'    => 'filled',
            'filled_by' => $user->id,
            'filled_at' => now(),
        ]);

        $shiftDrop->load('scheduleEntry.shiftTemplate', 'requester', 'filler', 'volunteers.user');

        broadcast(new ShiftDropFilled($shiftDrop))->toOthers();

        return response()->json($shiftDrop);
    }

    /**
     * Cancel an open shift drop (staff action -- own drops only).
     *
     * Only the original requester may cancel, and only while the drop is still
     * in "open" status. Sets the drop's status to "cancelled", preventing further
     * volunteering or selection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to cancel (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with status "cancelled".
     */
    public function cancel(Request $request, ShiftDrop $shiftDrop): JsonResponse
    {
        if ($shiftDrop->requested_by !== $request->user()->id) {
            return response()->json(['message' => 'You can only cancel your own drops.'], 403);
        }

        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This drop can no longer be cancelled.'], 422);
        }

        $shiftDrop->update(['status' => 'cancelled']);

        return response()->json($shiftDrop);
    }
}
