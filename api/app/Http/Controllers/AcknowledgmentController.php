<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcknowledgmentRequest;
use App\Models\Acknowledgment;
use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\PushItem;
use App\Models\Special;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AcknowledgmentController tracks which pre-shift items a user has reviewed.
 */
class AcknowledgmentController extends Controller
{
    /**
     * Record an acknowledgment for a specific pre-shift item.
     *
     * @param  \App\Http\Requests\StoreAcknowledgmentRequest  $request
     * @return \Illuminate\Http\JsonResponse  The acknowledgment record (created or updated).
     */
    public function store(StoreAcknowledgmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $typeMap = [
            'eighty_sixed' => EightySixed::class,
            'special' => Special::class,
            'push_item' => PushItem::class,
            'announcement' => Announcement::class,
        ];

        $modelClass = $typeMap[$validated['type']];
        $model = $modelClass::findOrFail($validated['id']);

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

    /**
     * Retrieve the acknowledgment status for all items the authenticated user has acknowledged.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Acknowledgments grouped by resource type.
     */
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

    /**
     * Return a per-user acknowledgment summary for the authenticated manager's location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Summary with total_items and per-user ack data.
     */
    public function summary(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $eightySixedIds = EightySixed::where('location_id', $locationId)
            ->whereNull('restored_at')
            ->pluck('id');

        $specialIds = Special::where('location_id', $locationId)
            ->where('is_active', true)
            ->pluck('id');

        $pushItemIds = PushItem::where('location_id', $locationId)
            ->where('is_active', true)
            ->pluck('id');

        $announcementIds = Announcement::where('location_id', $locationId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->pluck('id');

        $itemMap = [
            EightySixed::class => $eightySixedIds,
            Special::class     => $specialIds,
            PushItem::class    => $pushItemIds,
            Announcement::class => $announcementIds,
        ];

        $totalItems = $eightySixedIds->count()
                    + $specialIds->count()
                    + $pushItemIds->count()
                    + $announcementIds->count();

        $users = User::where('location_id', $locationId)->get();

        $usersData = $users->map(function (User $user) use ($itemMap, $totalItems) {
            $acknowledgedCount = 0;

            foreach ($itemMap as $type => $ids) {
                if ($ids->isEmpty()) {
                    continue;
                }

                $acknowledgedCount += Acknowledgment::where('user_id', $user->id)
                    ->where('acknowledgable_type', $type)
                    ->whereIn('acknowledgable_id', $ids)
                    ->count();
            }

            return [
                'user_id'            => $user->id,
                'user_name'          => $user->name,
                'role'               => $user->role,
                'total_items'        => $totalItems,
                'acknowledged_count' => $acknowledgedCount,
                'percentage'         => $totalItems > 0
                    ? round(($acknowledgedCount / $totalItems) * 100)
                    : 0,
            ];
        });

        return response()->json([
            'total_items' => $totalItems,
            'users'       => $usersData,
        ]);
    }
}
