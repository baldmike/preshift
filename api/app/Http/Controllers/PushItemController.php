<?php

namespace App\Http\Controllers;

use App\Events\PushItemCreated;
use App\Events\PushItemDeleted;
use App\Events\PushItemUpdated;
use App\Models\PushItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $pushItems = PushItem::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'creator')
            ->get();

        return response()->json($pushItems);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ]);

        $pushItem = PushItem::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'created_by' => $request->user()->id,
        ]);

        broadcast(new PushItemCreated($pushItem))->toOthers();

        return response()->json($pushItem, 201);
    }

    public function update(Request $request, PushItem $pushItem): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ]);

        $pushItem->update($validated);

        broadcast(new PushItemUpdated($pushItem))->toOthers();

        return response()->json($pushItem);
    }

    public function destroy(PushItem $pushItem): Response
    {
        $data = ['id' => $pushItem->id, 'location_id' => $pushItem->location_id];

        $pushItem->delete();

        broadcast(new PushItemDeleted($data))->toOthers();

        return response()->noContent();
    }
}
