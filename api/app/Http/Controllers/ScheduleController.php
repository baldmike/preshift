<?php

namespace App\Http\Controllers;

use App\Events\SchedulePublished;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for weekly schedules.
 *
 * Manages the lifecycle of weekly schedules: creation, viewing, publishing,
 * and unpublishing. Schedules hold the collection of shift entries for a
 * given week at a location. The publish/unpublish actions control visibility
 * to staff.
 */
class ScheduleController extends Controller
{
    /**
     * List schedules for the authenticated user's location.
     *
     * Returns current and upcoming weeks (week_start >= 2 weeks ago) with
     * a count of entries. Ordered by week_start ascending.
     *
     * @return \Illuminate\Http\JsonResponse  JSON array of Schedule records.
     */
    public function index(Request $request): JsonResponse
    {
        $schedules = Schedule::where('location_id', $request->user()->location_id)
            ->where('week_start', '>=', now()->subWeeks(2)->startOfWeek()->toDateString())
            ->withCount('entries')
            ->orderBy('week_start')
            ->get();

        return response()->json($schedules);
    }

    /**
     * Show a single schedule with all its entries and related data.
     *
     * Eager-loads each entry's user and shift template so the schedule
     * builder can render the full grid without N+1 queries.
     *
     * @param  Schedule $schedule  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The schedule with entries.
     */
    public function show(Schedule $schedule): JsonResponse
    {
        $schedule->load('entries.user', 'entries.shiftTemplate', 'publisher');

        return response()->json($schedule);
    }

    /**
     * Create a new weekly schedule.
     *
     * Validation:
     *   - week_start: required, valid date — should be a Monday.
     *
     * The schedule is created in "draft" status. A unique constraint on
     * (location_id, week_start) prevents duplicates.
     *
     * @return \Illuminate\Http\JsonResponse  The created schedule with 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => 'required|date',
        ]);

        $schedule = Schedule::create([
            'location_id' => $request->user()->location_id,
            'week_start'  => $validated['week_start'],
            'status'      => 'draft',
        ]);

        return response()->json($schedule, 201);
    }

    /**
     * Update a schedule's basic info (e.g. change week_start while still draft).
     *
     * @param  Schedule $schedule  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated schedule.
     */
    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => 'required|date',
        ]);

        $schedule->update($validated);

        return response()->json($schedule);
    }

    /**
     * Publish a schedule, making it visible to all staff at the location.
     *
     * Sets the status to "published", records who published and when, and
     * broadcasts a SchedulePublished event via Reverb so connected staff
     * see the schedule immediately.
     *
     * @param  Schedule $schedule  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The published schedule.
     */
    public function publish(Request $request, Schedule $schedule): JsonResponse
    {
        $schedule->update([
            'status'       => 'published',
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        broadcast(new SchedulePublished($schedule))->toOthers();

        return response()->json($schedule);
    }

    /**
     * Unpublish a schedule, reverting it to draft status for editing.
     *
     * Staff will no longer see this schedule until it is republished.
     *
     * @param  Schedule $schedule  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated schedule.
     */
    public function unpublish(Schedule $schedule): JsonResponse
    {
        $schedule->update(['status' => 'draft']);

        return response()->json($schedule);
    }

    /**
     * Get the authenticated staff member's upcoming shifts.
     *
     * Returns schedule entries for the current user across all published
     * schedules where the shift date is today or in the future. Eager-loads
     * the shift template and parent schedule for display.
     *
     * @return \Illuminate\Http\JsonResponse  JSON array of ScheduleEntry records.
     */
    public function myShifts(Request $request): JsonResponse
    {
        $entries = ScheduleEntry::where('user_id', $request->user()->id)
            ->whereHas('schedule', function ($q) use ($request) {
                $q->where('location_id', $request->user()->location_id)
                  ->where('status', 'published');
            })
            ->where('date', '>=', now()->toDateString())
            ->with('shiftTemplate', 'schedule')
            ->orderBy('date')
            ->get();

        return response()->json($entries);
    }
}
