<?php

namespace App\Http\Controllers;

use App\Events\ShiftDropFilled;
use App\Events\ShiftDropRequested;
use App\Events\ShiftDropVolunteered;
use App\Http\Requests\StoreShiftDropRequest;
use App\Http\Resources\ShiftDropResource;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShiftDropController manages the shift-drop workflow for a location.
 */
class ShiftDropController extends Controller
{
    /**
     * List shift drops visible to the authenticated user.
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
                $q->where('requested_by', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('status', 'open')
                         ->whereHas('scheduleEntry', function ($q3) use ($user) {
                             $q3->where('role', $user->role);
                         });
                  });
            });
        }

        $drops = $query->orderByDesc('created_at')->get();

        return response()->json(ShiftDropResource::collection($drops));
    }

    /**
     * Create a new shift drop request (staff action -- can only drop own shifts).
     *
     * @param  \App\Http\Requests\StoreShiftDropRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created shift drop with a 201 status.
     */
    public function store(StoreShiftDropRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        return response()->json(new ShiftDropResource($drop), 201);
    }

    /**
     * Volunteer to pick up an open shift drop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to volunteer for (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with volunteers loaded.
     */
    public function volunteer(Request $request, ShiftDrop $shiftDrop): JsonResponse
    {
        $this->authorize('volunteer', $shiftDrop);

        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This shift drop is no longer available.'], 422);
        }

        if ($shiftDrop->requested_by === $request->user()->id) {
            return response()->json(['message' => 'You cannot volunteer for your own drop.'], 422);
        }

        $entryRole = $shiftDrop->scheduleEntry->role;
        if ($request->user()->role !== $entryRole) {
            return response()->json(['message' => 'You must have the same role to pick up this shift.'], 422);
        }

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

        return response()->json(new ShiftDropResource($shiftDrop));
    }

    /**
     * Select a volunteer to fill the shift drop (manager action).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to fill (via route model binding).
     * @param  \App\Models\User          $user       The volunteer to assign the shift to (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with filler and volunteers loaded.
     */
    public function select(Request $request, ShiftDrop $shiftDrop, User $user): JsonResponse
    {
        $this->authorize('select', $shiftDrop);

        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This shift drop is no longer available.'], 422);
        }

        $volunteer = $shiftDrop->volunteers()->where('user_id', $user->id)->first();
        if (! $volunteer) {
            return response()->json(['message' => 'This user has not volunteered for this drop.'], 422);
        }

        $volunteer->update(['selected' => true]);

        $shiftDrop->scheduleEntry->update(['user_id' => $user->id]);

        $shiftDrop->update([
            'status'    => 'filled',
            'filled_by' => $user->id,
            'filled_at' => now(),
        ]);

        $shiftDrop->load('scheduleEntry.shiftTemplate', 'requester', 'filler', 'volunteers.user');

        broadcast(new ShiftDropFilled($shiftDrop))->toOthers();

        return response()->json(new ShiftDropResource($shiftDrop));
    }

    /**
     * Cancel an open shift drop (staff action -- own drops only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftDrop     $shiftDrop  The shift drop to cancel (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated shift drop with status "cancelled".
     */
    public function cancel(Request $request, ShiftDrop $shiftDrop): JsonResponse
    {
        $this->authorize('cancel', $shiftDrop);

        if ($shiftDrop->status !== 'open') {
            return response()->json(['message' => 'This drop can no longer be cancelled.'], 422);
        }

        $shiftDrop->update(['status' => 'cancelled']);

        return response()->json(new ShiftDropResource($shiftDrop));
    }
}
