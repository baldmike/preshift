<?php

namespace App\Http\Controllers;

use App\Events\ItemEightySixed;
use App\Events\ItemRestored;
use App\Models\EightySixed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EightySixedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $items = EightySixed::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'user')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $item = EightySixed::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'eighty_sixed_by' => $request->user()->id,
        ]);

        broadcast(new ItemEightySixed($item))->toOthers();

        return response()->json($item, 201);
    }

    public function restore(EightySixed $eightySixed): JsonResponse
    {
        $eightySixed->update(['restored_at' => now()]);

        broadcast(new ItemRestored($eightySixed))->toOthers();

        return response()->json($eightySixed);
    }
}
