<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftTemplateRequest;
use App\Models\ShiftTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CRUD controller for shift templates.
 *
 * Shift templates define reusable shift types per location (e.g. "Lunch
 * 10:30–3:00", "Dinner 4:00–11:00"). Managers create templates once and
 * reference them when building weekly schedules. All records are scoped
 * to the authenticated user's location.
 */
class ShiftTemplateController extends Controller
{
    /**
     * List all shift templates for the authenticated user's location.
     *
     * @return \Illuminate\Http\JsonResponse  JSON array of ShiftTemplate records.
     */
    public function index(Request $request): JsonResponse
    {
        $templates = ShiftTemplate::where('location_id', $request->user()->location_id)
            ->orderBy('start_time')
            ->get();

        return response()->json($templates);
    }

    /**
     * Create a new shift template.
     *
     * @return \Illuminate\Http\JsonResponse  The created template with 201 status.
     */
    public function store(StoreShiftTemplateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $template = ShiftTemplate::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
        ]);

        return response()->json($template, 201);
    }

    /**
     * Update an existing shift template.
     *
     * @param  ShiftTemplate $shiftTemplate  Resolved via route model binding.
     * @return \Illuminate\Http\JsonResponse  The updated template.
     */
    public function update(StoreShiftTemplateRequest $request, ShiftTemplate $shiftTemplate): JsonResponse
    {
        $validated = $request->validated();

        $shiftTemplate->update($validated);

        return response()->json($shiftTemplate);
    }

    /**
     * Delete a shift template permanently.
     *
     * @param  ShiftTemplate $shiftTemplate  Resolved via route model binding.
     * @return \Illuminate\Http\Response      204 No Content.
     */
    public function destroy(ShiftTemplate $shiftTemplate): Response
    {
        $shiftTemplate->delete();

        return response()->noContent();
    }
}
