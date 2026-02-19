<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\PushItem;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locationId = $user->location_id;

        $eightySixed = EightySixed::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'user')
            ->get();

        $specials = Special::where('location_id', $locationId)
            ->current()
            ->with('menuItem', 'creator')
            ->get();

        $pushItems = PushItem::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'creator')
            ->get();

        $announcements = Announcement::where('location_id', $locationId)
            ->active()
            ->forRole($user->role)
            ->with('poster')
            ->get();

        $acknowledgments = $user->acknowledgments->map(function ($ack) {
            return [
                'type' => $ack->acknowledgable_type,
                'id' => $ack->acknowledgable_id,
            ];
        });

        return response()->json([
            'eighty_sixed' => $eightySixed,
            'specials' => $specials,
            'push_items' => $pushItems,
            'announcements' => $announcements,
            'acknowledgments' => $acknowledgments,
        ]);
    }
}
