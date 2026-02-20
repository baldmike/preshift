<?php

namespace App\Http\Controllers;

use App\Events\SwapOffered;
use App\Events\SwapRequested;
use App\Events\SwapResolved;
use App\Models\ScheduleEntry;
use App\Models\SwapRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for shift swap requests.
 *
 * Handles the full swap lifecycle: staff request a swap on their shift,
 * other eligible staff can offer to pick it up, and managers approve or
 * deny the swap. On approval the schedule entry is reassigned.
 *
 * Swap status flow: pending → offered → approved/denied (or cancelled)
 */
class SwapRequestController extends Controller
{
    /**
     * List swap requests visible to the authenticated user.
     *
     * - Managers see all swap requests for their location.
     * - Staff see requests they created, requests targeting them, and open
     *   requests (no target) they could pick up.
     *
     * @return \Illuminate\Http\JsonResponse  JSON array of SwapRequest records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SwapRequest::whereHas('scheduleEntry.schedule', function ($q) use ($user) {
            $q->where('location_id', $user->location_id);
        })->with('scheduleEntry.shiftTemplate', 'scheduleEntry.user', 'requester', 'picker');

        // Staff only see relevant swap requests (their own, targeted at them, or open)
        if ($user->isStaff()) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id)
                  ->orWhere('target_user_id', $user->id)
                  ->orWhereNull('target_user_id');
            });
        }

        $swaps = $query->orderByDesc('created_at')->get();

        return response()->json($swaps);
    }

    /**
     * Create a new swap request on one of the authenticated user's shifts.
     *
     * Validation:
     *   - schedule_entry_id: required, must exist and belong to the requesting user.
     *   - target_user_id: optional, specific person to swap with.
     *   - reason: optional, max 255 chars.
     *
     * Guards: the authenticated user must own the schedule entry.
     *
     * @return \Illuminate\Http\JsonResponse  The created swap request, 201.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_entry_id' => 'required|exists:schedule_entries,id',
            'target_user_id'    => 'nullable|exists:users,id',
            'reason'            => 'nullable|string|max:255',
        ]);

        // Ensure the requesting user owns the shift
        $entry = ScheduleEntry::findOrFail($validated['schedule_entry_id']);
        if ($entry->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only request swaps on your own shifts.'], 403);
        }

        $swap = SwapRequest::create([
            ...$validated,
            'requested_by' => $request->user()->id,
            'status'        => 'pending',
        ]);

        $swap->load('scheduleEntry.shiftTemplate', 'requester');

        broadcast(new SwapRequested($swap))->toOthers();

        return response()->json($swap, 201);
    }

    /**
     * Offer to pick up a swap request.
     *
     * Sets the authenticated user as the person willing to cover the shift
     * and advances the status to "offered". Only eligible if status is
     * "pending" and the user isn't the original requester.
     *
     * @param  SwapRequest $swapRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated swap request.
     */
    public function offer(Request $request, SwapRequest $swapRequest): JsonResponse
    {
        if ($swapRequest->status !== 'pending') {
            return response()->json(['message' => 'This swap is no longer available.'], 422);
        }

        if ($swapRequest->requested_by === $request->user()->id) {
            return response()->json(['message' => 'You cannot pick up your own swap request.'], 422);
        }

        $swapRequest->update([
            'picked_up_by' => $request->user()->id,
            'status'        => 'offered',
        ]);

        $swapRequest->load('scheduleEntry.shiftTemplate', 'requester', 'picker');

        broadcast(new SwapOffered($swapRequest))->toOthers();

        return response()->json($swapRequest);
    }

    /**
     * Approve a swap request (manager action).
     *
     * Reassigns the schedule entry to the person who offered to pick it up,
     * marks the swap as approved, and broadcasts the resolution.
     *
     * @param  SwapRequest $swapRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The approved swap request.
     */
    public function approve(Request $request, SwapRequest $swapRequest): JsonResponse
    {
        if ($swapRequest->status !== 'offered') {
            return response()->json(['message' => 'Swap must have an offer before approval.'], 422);
        }

        // Reassign the schedule entry to the person who picked it up
        $swapRequest->scheduleEntry->update([
            'user_id' => $swapRequest->picked_up_by,
        ]);

        $swapRequest->update([
            'status'      => 'approved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $swapRequest->load('scheduleEntry.shiftTemplate', 'requester', 'picker', 'resolver');

        broadcast(new SwapResolved($swapRequest))->toOthers();

        return response()->json($swapRequest);
    }

    /**
     * Deny a swap request (manager action).
     *
     * Marks the swap as denied without changing the schedule entry.
     *
     * @param  SwapRequest $swapRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The denied swap request.
     */
    public function deny(Request $request, SwapRequest $swapRequest): JsonResponse
    {
        if (in_array($swapRequest->status, ['approved', 'denied', 'cancelled'])) {
            return response()->json(['message' => 'This swap has already been resolved.'], 422);
        }

        $swapRequest->update([
            'status'      => 'denied',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $swapRequest->load('scheduleEntry.shiftTemplate', 'requester', 'picker', 'resolver');

        broadcast(new SwapResolved($swapRequest))->toOthers();

        return response()->json($swapRequest);
    }

    /**
     * Cancel a swap request (staff action — can only cancel their own).
     *
     * @param  SwapRequest $swapRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The cancelled swap request.
     */
    public function cancel(Request $request, SwapRequest $swapRequest): JsonResponse
    {
        if ($swapRequest->requested_by !== $request->user()->id) {
            return response()->json(['message' => 'You can only cancel your own swap requests.'], 403);
        }

        if (in_array($swapRequest->status, ['approved', 'denied', 'cancelled'])) {
            return response()->json(['message' => 'This swap has already been resolved.'], 422);
        }

        $swapRequest->update(['status' => 'cancelled']);

        return response()->json($swapRequest);
    }
}
