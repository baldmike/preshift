<?php

namespace App\Http\Controllers;

use App\Events\SpecialCreated;
use App\Events\SpecialDeleted;
use App\Events\SpecialUpdated;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpecialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $specials = Special::where('location_id', $locationId)
            ->current()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json($specials);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,weekly,monthly,limited_time',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ]);

        $special = Special::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        broadcast(new SpecialCreated($special))->toOthers();

        return response()->json($special, 201);
    }

    public function update(Request $request, Special $special): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,weekly,monthly,limited_time',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ]);

        $special->update($validated);

        broadcast(new SpecialUpdated($special))->toOthers();

        return response()->json($special);
    }

    public function destroy(Special $special): Response
    {
        $data = ['id' => $special->id, 'location_id' => $special->location_id];

        $special->delete();

        broadcast(new SpecialDeleted($data))->toOthers();

        return response()->noContent();
    }
}
