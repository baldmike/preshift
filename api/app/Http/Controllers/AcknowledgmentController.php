<?php

namespace App\Http\Controllers;

use App\Models\Acknowledgment;
use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\PushItem;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcknowledgmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:eighty_sixed,special,push_item,announcement',
            'id' => 'required|integer',
        ]);

        $typeMap = [
            'eighty_sixed' => EightySixed::class,
            'special' => Special::class,
            'push_item' => PushItem::class,
            'announcement' => Announcement::class,
        ];

        $modelClass = $typeMap[$request->type];
        $model = $modelClass::findOrFail($request->id);

        $acknowledgment = Acknowledgment::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'acknowledgable_type' => $modelClass,
                'acknowledgable_id' => $model->id,
            ],
            [
                'acknowledged_at' => now(),
            ]
        );

        return response()->json($acknowledgment);
    }

    public function status(Request $request): JsonResponse
    {
        $acknowledgments = $request->user()
            ->acknowledgments()
            ->get()
            ->groupBy('acknowledgable_type')
            ->map(function ($group) {
                return $group->map(function ($ack) {
                    return [
                        'id' => $ack->acknowledgable_id,
                        'acknowledged_at' => $ack->acknowledged_at,
                    ];
                });
            });

        return response()->json($acknowledgments);
    }
}
