<?php

namespace App\Http\Controllers;

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
 *
 * Staff members are expected to acknowledge (confirm they have read/seen) various
 * pre-shift items such as 86'd items, specials, push items, and announcements.
 * This controller uses a polymorphic relationship pattern: the Acknowledgment model
 * stores a reference to any acknowledgeable resource type via `acknowledgable_type`
 * and `acknowledgable_id`. This enables a single acknowledgments table to track
 * read-receipts across all four resource types.
 */
class AcknowledgmentController extends Controller
{
    /**
     * Record an acknowledgment for a specific pre-shift item.
     *
     * Validates the incoming type (one of four resource types) and ID, resolves
     * the corresponding Eloquent model, and creates or updates an acknowledgment
     * record for the authenticated user. Uses updateOrCreate so that re-acknowledging
     * the same item simply refreshes the timestamp rather than creating duplicates.
     *
     * Validation rules:
     * - type: required, must be one of: eighty_sixed, special, push_item, announcement.
     * - id: required, integer -- the primary key of the resource being acknowledged.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The acknowledgment record (created or updated).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:eighty_sixed,special,push_item,announcement', // Which resource type is being acknowledged
            'id' => 'required|integer',                                           // Primary key of the resource
        ]);

        // Map the short type strings from the request to their fully qualified Eloquent model classes.
        // This allows the polymorphic acknowledgable relationship to reference the correct model.
        $typeMap = [
            'eighty_sixed' => EightySixed::class,
            'special' => Special::class,
            'push_item' => PushItem::class,
            'announcement' => Announcement::class,
        ];

        // Resolve the model class from the type map and find the resource, or fail with a 404.
        $modelClass = $typeMap[$request->type];
        $model = $modelClass::findOrFail($request->id);

        // Create a new acknowledgment or update the existing one for this user + resource combination.
        // The unique key is (user_id, acknowledgable_type, acknowledgable_id).
        // If the user has already acknowledged this item, the timestamp is refreshed.
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
     * Returns all of the user's acknowledgment records grouped by the polymorphic
     * acknowledgable_type (the fully qualified model class name). Within each group,
     * each entry contains the resource ID and the timestamp when it was acknowledged.
     * This allows the client to render read/unread indicators for pre-shift items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Acknowledgments grouped by resource type.
     */
    public function status(Request $request): JsonResponse
    {
        // Fetch all acknowledgment records for the authenticated user,
        // group them by the polymorphic type (model class), and map each
        // group to a simplified array of {id, acknowledged_at} objects.
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
     * Gathers all currently active acknowledgeable items across the four content
     * types (86'd items, specials, push items, announcements) scoped to the
     * manager's location, then counts how many of those each user at the location
     * has acknowledged. This powers the manager's Acknowledgment Tracker view.
     *
     * Response shape:
     *   {
     *     "total_items": 12,
     *     "users": [
     *       {
     *         "user_id": 1,
     *         "user_name": "Jane Doe",
     *         "role": "server",
     *         "total_items": 12,
     *         "acknowledged_count": 10,
     *         "percentage": 83
     *       },
     *       ...
     *     ]
     *   }
     *
     * Guards against division by zero when no active items exist (returns 0%).
     *
     * Route: GET /api/acknowledgments/summary
     * Middleware: auth:sanctum, location, role:admin,manager
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Summary with total_items and per-user ack data.
     */
    public function summary(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        // ── Gather IDs of all active acknowledgeable items at this location ──
        // Each content type has its own "active" criteria:
        //   - 86'd: not yet restored (restored_at IS NULL)
        //   - Specials: explicitly marked active (is_active = true)
        //   - Push Items: explicitly marked active (is_active = true)
        //   - Announcements: not expired (expires_at IS NULL or in the future)
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
                // Include announcements that either never expire or haven't expired yet.
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->pluck('id');

        // Map each polymorphic model class to its active item IDs.
        // Used below to count per-user acknowledgments across all four types.
        $itemMap = [
            EightySixed::class => $eightySixedIds,
            Special::class     => $specialIds,
            PushItem::class    => $pushItemIds,
            Announcement::class => $announcementIds,
        ];

        // Total number of active items across all categories.
        $totalItems = $eightySixedIds->count()
                    + $specialIds->count()
                    + $pushItemIds->count()
                    + $announcementIds->count();

        // ── Build per-user summary ──────────────────────────────────────────
        // For every user at this location, count how many of the active items
        // they have acknowledged by querying the polymorphic acknowledgments table.
        $users = User::where('location_id', $locationId)->get();

        $usersData = $users->map(function (User $user) use ($itemMap, $totalItems) {
            $acknowledgedCount = 0;

            // For each content type, count how many of the active IDs this user
            // has a matching acknowledgment record for.
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
                // Guard against division by zero when there are no active items.
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
