<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementDeleted;
use App\Events\AnnouncementPosted;
use App\Events\AnnouncementUpdated;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AnnouncementController manages staff announcements for a location.
 */
class AnnouncementController extends Controller
{
    /**
     * List all active announcements for the authenticated user's location and role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of announcements relevant to the user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locationId = $user->location_id;

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
     * @param  \App\Http\Requests\StoreAnnouncementRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created announcement with a 201 status.
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $announcement = Announcement::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'posted_by' => $request->user()->id,
        ]);

        broadcast(new AnnouncementPosted($announcement))->toOthers();

        return response()->json($announcement, 201);
    }

    /**
     * Update an existing announcement.
     *
     * @param  \App\Http\Requests\StoreAnnouncementRequest   $request
     * @param  \App\Models\Announcement   $announcement  The announcement to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated announcement.
     */
    public function update(StoreAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validated();

        $announcement->update($validated);

        broadcast(new AnnouncementUpdated($announcement))->toOthers();

        return response()->json($announcement);
    }

    /**
     * Delete an announcement permanently.
     *
     * @param  \App\Models\Announcement  $announcement  The announcement to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(Announcement $announcement): Response
    {
        $data = ['id' => $announcement->id, 'location_id' => $announcement->location_id];

        $announcement->delete();

        broadcast(new AnnouncementDeleted($data))->toOthers();

        return response()->noContent();
    }
}
