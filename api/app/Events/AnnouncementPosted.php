<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a new announcement is posted at a location.
 * Announcements are manager-to-staff messages (e.g., "VIP tonight", "staff
 * meeting Friday") that may be targeted at specific roles and may expire.
 *
 * Trigger:  Dispatched from AnnouncementController::store() after a manager or
 *           admin creates a new Announcement record.
 * Channel:  Private "location.{id}" -- scoped to the announcement's location.
 * Payload:  The full Announcement model as an array (id, title, body, priority,
 *           target_roles, posted_by, expires_at, timestamps).
 * Purpose:  Delivers a real-time WebSocket notification so all connected staff
 *           at the location see the new announcement immediately, which is
 *           especially important for "urgent" priority messages.
 */
class AnnouncementPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Announcement  $announcement  The newly created announcement,
     *                                      including its priority level,
     *                                      optional role targeting, and
     *                                      expiration date.
     */
    public function __construct(public Announcement $announcement)
    {
        //
    }

    /**
     * Broadcast on the private location channel. Role-based filtering (if
     * target_roles is set) is handled client-side; the channel itself is
     * scoped to the entire location.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->announcement->location_id);
    }

    /**
     * Send the full Announcement record so clients can display it on the
     * announcements board without a follow-up API request.
     */
    public function broadcastWith(): array
    {
        return $this->announcement->toArray();
    }
}
