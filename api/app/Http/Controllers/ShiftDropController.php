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

class ShiftDropController extends Controller
{
    /**
     * List shift drops visible to the authenticated user.
     *
     * Staff see open drops matching their role plus their own drops.
     * Managers see all drops for the location.
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
     * Drop a shift (staff action — can only drop own shifts).
     * Creates the drop with status=open and broadcasts to all staff.
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
     * Validates the drop is open, user has the same role, and isn't the requester.
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
     * Manager selects a volunteer to fill the shift drop.
     * Reassigns the schedule entry and marks the drop as filled.
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
     * Cancel an open shift drop (staff action — own drops only).
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
