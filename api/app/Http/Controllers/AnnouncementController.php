<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementDeleted;
use App\Events\AnnouncementPosted;
use App\Events\AnnouncementUpdated;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:normal,important,urgent',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'location_id' => $request->user()->location_id,
            'posted_by' => $request->user()->id,
        ]);

        broadcast(new AnnouncementPosted($announcement))->toOthers();

        return response()->json($announcement, 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:normal,important,urgent',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($validated);

        broadcast(new AnnouncementUpdated($announcement))->toOthers();

        return response()->json($announcement);
    }

    public function destroy(Announcement $announcement): Response
    {
        $data = ['id' => $announcement->id, 'location_id' => $announcement->location_id];

        $announcement->delete();

        broadcast(new AnnouncementDeleted($data))->toOthers();

        return response()->noContent();
    }
}
