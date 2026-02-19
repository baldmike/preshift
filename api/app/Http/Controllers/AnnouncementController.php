<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementDeleted;
use App\Events\AnnouncementPosted;
use App\Events\AnnouncementUpdated;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AnnouncementController manages staff announcements for a location.
 *
 * Announcements are messages posted by managers or admins that need to be
 * communicated to staff during pre-shift meetings. They support priority levels
 * (normal, important, urgent), role-based targeting (e.g., only for servers),
 * and optional expiration dates. This controller provides full CRUD operations
 * scoped to the authenticated user's location, with real-time WebSocket
 * broadcasting for all write operations.
 */
class AnnouncementController extends Controller
{
    /**
     * List all active announcements for the authenticated user's location and role.
     *
     * Uses two scopes on the Announcement model:
     * - `active()`: excludes expired announcements (where expires_at has passed).
     * - `forRole()`: filters announcements to those targeting the user's role,
     *   or those with no role restriction (visible to all roles).
     *
     * Eager-loads the 'poster' relationship (the User who posted the announcement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of announcements relevant to the user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        // Scope to the authenticated user's assigned location.
        $locationId = $user->location_id;

        // Retrieve active announcements targeted at the user's role, with the poster relationship.
        $announcements = Announcement::where('location_id', $locationId)
            ->active()
            ->forRole($user->role)
            ->with('poster')
            ->get();

        return response()->json($announcements);
    }

    /**
     * Create a new announcement for the user's location.
     *
     * Validates the request, creates the announcement record, and broadcasts
     * an AnnouncementPosted event for real-time client updates.
     *
     * Validation rules:
     * - title: required, string, max 255 chars -- the announcement headline.
     * - body: required, string -- the full content/message of the announcement.
     * - priority: required, must be one of: normal, important, urgent.
     * - target_roles: optional array of role strings -- restricts which roles see the announcement.
     *   If null/omitted, the announcement is visible to all roles.
     * - expires_at: optional date -- when the announcement should auto-expire.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created announcement with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',                // Announcement headline
            'body' => 'required|string',                         // Full announcement content
            'priority' => 'required|in:normal,important,urgent', // Urgency level for display styling
            'target_roles' => 'nullable|array',                  // Array of roles to target (e.g. ['server', 'bartender'])
            'expires_at' => 'nullable|date',                     // Auto-expiration date (null = no expiration)
        ]);

        // Create the announcement, associating it with the user's location and recording the poster.
        $announcement = Announcement::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'posted_by' => $request->user()->id,
        ]);

        // Broadcast AnnouncementPosted event via WebSocket to all other connected clients.
        broadcast(new AnnouncementPosted($announcement))->toOthers();

        return response()->json($announcement, 201);
    }

    /**
     * Update an existing announcement.
     *
     * Validates the same fields as store(), applies the changes to the given
     * Announcement model (resolved via route model binding), and broadcasts an
     * AnnouncementUpdated event for real-time synchronization.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Announcement   $announcement  The announcement to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated announcement.
     */
    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:normal,important,urgent',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        // Apply validated changes to the announcement record.
        $announcement->update($validated);

        // Broadcast AnnouncementUpdated event to notify other clients of the change.
        broadcast(new AnnouncementUpdated($announcement))->toOthers();

        return response()->json($announcement);
    }

    /**
     * Delete an announcement permanently.
     *
     * Captures the announcement's ID and location_id before deletion so they can
     * be included in the AnnouncementDeleted broadcast event, allowing clients to
     * remove the correct item from their local state.
     *
     * @param  \App\Models\Announcement  $announcement  The announcement to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Announcement $announcement): Response
    {
        // Capture identifiers before deletion for the broadcast payload.
        $data = ['id' => $announcement->id, 'location_id' => $announcement->location_id];

        // Permanently delete the announcement record from the database.
        $announcement->delete();

        // Broadcast AnnouncementDeleted event so other clients can remove the item from their UI.
        broadcast(new AnnouncementDeleted($data))->toOthers();

        return response()->noContent();
    }
}
