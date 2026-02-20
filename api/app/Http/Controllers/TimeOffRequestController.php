<?php

namespace App\Http\Controllers;

use App\Events\TimeOffResolved;
use App\Models\TimeOffRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for time-off requests.
 *
 * Staff submit time-off requests for a date range. Managers approve or deny
 * them. Approved time-off is visible in the schedule builder to prevent
 * conflicts.
 *
 * Time-off status flow: pending → approved/denied
 */
class TimeOffRequestController extends Controller
{
    /**
     * List time-off requests.
     *
     * - Managers see all requests for their location.
     * - Staff see only their own requests.
     *
     * @return \Illuminate\Http\JsonResponse  JSON array of TimeOffRequest records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = TimeOffRequest::where('location_id', $user->location_id)
            ->with('user', 'resolver')
            ->orderByDesc('created_at');

        // Staff only see their own requests
        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }

    /**
     * Submit a new time-off request.
     *
     * Validation:
     *   - start_date: required, valid date, today or later.
     *   - end_date: required, valid date, on or after start_date.
     *   - reason: optional, max 255 chars.
     *
     * @return \Illuminate\Http\JsonResponse  The created request with 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:255',
        ]);

        $timeOff = TimeOffRequest::create([
            ...$validated,
            'user_id'     => $request->user()->id,
            'location_id' => $request->user()->location_id,
            'status'      => 'pending',
        ]);

        $timeOff->load('user');

        return response()->json($timeOff, 201);
    }

    /**
     * Approve a time-off request (manager action).
     *
     * Marks the request as approved and broadcasts the resolution so the
     * staff member is notified in real time.
     *
     * @param  TimeOffRequest $timeOffRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse    The approved request.
     */
    public function approve(Request $request, TimeOffRequest $timeOffRequest): JsonResponse
    {
        if ($timeOffRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been resolved.'], 422);
        }

        $timeOffRequest->update([
            'status'      => 'approved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $timeOffRequest->load('user', 'resolver');

        broadcast(new TimeOffResolved($timeOffRequest))->toOthers();

        return response()->json($timeOffRequest);
    }

    /**
     * Deny a time-off request (manager action).
     *
     * @param  TimeOffRequest $timeOffRequest  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse    The denied request.
     */
    public function deny(Request $request, TimeOffRequest $timeOffRequest): JsonResponse
    {
        if ($timeOffRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been resolved.'], 422);
        }

        $timeOffRequest->update([
            'status'      => 'denied',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $timeOffRequest->load('user', 'resolver');

        broadcast(new TimeOffResolved($timeOffRequest))->toOthers();

        return response()->json($timeOffRequest);
    }
}
