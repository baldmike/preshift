<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController manages in-app notifications for managers and admins.
 *
 * Provides endpoints to list notifications (with optional unread filter),
 * mark individual notifications as read, and bulk-mark all as read. Notifications
 * are scoped to the authenticated user via Laravel's built-in notification system.
 */
class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications (newest 50).
     *
     * Optionally filters to unread-only when the `unread_only` query parameter
     * is truthy. Also returns the total unread count for badge display.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Notifications array and unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->take(50)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? null,
                'title' => $n->data['title'] ?? null,
                'body' => $n->data['body'] ?? null,
                'link' => $n->data['link'] ?? null,
                'source_id' => $n->data['source_id'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ];
        });

        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read by its UUID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $id  The notification UUID.
     * @return \Illuminate\Http\JsonResponse  Confirmation message.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all of the authenticated user's unread notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  Confirmation message.
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
