<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\EightySixedResource;
use App\Http\Resources\PushItemResource;
use App\Http\Resources\SpecialResource;
use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\PushItem;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PreShiftController aggregates all pre-shift meeting data into a single endpoint.
 *
 * This controller serves as the main dashboard data source, combining 86'd items,
 * specials, push items, announcements, and the current user's acknowledgment status
 * into one consolidated JSON response. It is scoped to the authenticated user's
 * location so that staff only see information relevant to their restaurant/bar.
 */
class PreShiftController extends Controller
{
    /**
     * Retrieve all pre-shift meeting data for the authenticated user's location.
     *
     * Fetches active/current records from four resource types (86'd items, specials,
     * push items, and announcements), all scoped to the user's location_id. Also
     * returns the user's acknowledgment records so the client can determine which
     * items the user has already reviewed.
     *
     * Query scoping:
     * - EightySixed: uses the `active()` scope to exclude restored items.
     * - Specials: uses the `current()` scope to include only time-relevant specials.
     * - PushItems: uses the `active()` scope to exclude inactive push items.
     * - Announcements: uses both `active()` (not expired) and `forRole()` (targeted
     *   at the user's role, e.g. server, bartender, manager) scopes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A consolidated JSON payload containing all pre-shift data.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        // Scope all queries to the authenticated user's assigned location.
        $locationId = $user->location_id;

        // Fetch 86'd items that have not been restored, with related menu item and reporting user.
        $eightySixed = EightySixed::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'user')
            ->get();

        // Fetch specials that are currently active/in-season, with related menu item and creator.
        $specials = Special::where('location_id', $locationId)
            ->current()
            ->with('menuItem', 'creator')
            ->get();

        // Fetch push items that are currently active, with related menu item and creator.
        $pushItems = PushItem::where('location_id', $locationId)
            ->active()
            ->with('menuItem', 'creator')
            ->get();

        // Fetch announcements that are active and targeted at the user's role.
        // The forRole() scope filters announcements by the user's role (e.g. server, bartender).
        $announcements = Announcement::where('location_id', $locationId)
            ->active()
            ->forRole($user->role)
            ->with('poster')
            ->get();

        // Map the user's polymorphic acknowledgment records into a simple type/id structure.
        // This lets the client know which items have already been acknowledged by this user.
        $acknowledgments = $user->acknowledgments->map(function ($ack) {
            return [
                'type' => $ack->acknowledgable_type,   // Fully qualified model class name
                'id' => $ack->acknowledgable_id,        // ID of the acknowledged resource
            ];
        });

        // Return all pre-shift data in a single consolidated response.
        return response()->json([
            'eighty_sixed' => EightySixedResource::collection($eightySixed),
            'specials' => SpecialResource::collection($specials),
            'push_items' => PushItemResource::collection($pushItems),
            'announcements' => AnnouncementResource::collection($announcements),
            'acknowledgments' => $acknowledgments,
        ]);
    }
}
