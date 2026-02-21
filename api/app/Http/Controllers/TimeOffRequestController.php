<?php

namespace App\Http\Controllers;

use App\Events\TimeOffResolved;
use App\Http\Requests\StoreTimeOffRequestRequest;
use App\Http\Resources\TimeOffRequestResource;
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
     * @return \Illuminate\Http\JsonResponse  JSON array of TimeOffRequest records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = TimeOffRequest::where('location_id', $user->location_id)
            ->with('user', 'resolver')
            ->orderByDesc('created_at');

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return response()->json(TimeOffRequestResource::collection($query->get()));
    }

    /**
     * Submit a new time-off request.
     *
     * @return \Illuminate\Http\JsonResponse  The created request with 201 status.
     */
    public function store(StoreTimeOffRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $timeOff = TimeOffRequest::create([
            ...$validated,
            'user_id'     => $request->user()->id,
            'location_id' => $request->user()->location_id,
            'status'      => 'pending',
        ]);

        $timeOff->load('user');

        return response()->json(new TimeOffRequestResource($timeOff), 201);
    }

    /**
     * Approve a time-off request (manager action).
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

        return response()->json(new TimeOffRequestResource($timeOffRequest));
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

        return response()->json(new TimeOffRequestResource($timeOffRequest));
    }
}
