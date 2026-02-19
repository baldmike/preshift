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
 * Broadcast event fired when an existing announcement is edited (e.g., its
 * body, priority, target roles, or expiration date changes).
 *
 * Trigger:  Dispatched from AnnouncementController::update() after the
 *           Announcement record is saved with new values.
 * Channel:  Private "location.{id}" -- scoped to the announcement's location.
 * Payload:  The full updated Announcement model as an array so clients can
 *           replace the stale version in their local state.
 * Purpose:  Ensures connected front-end clients at the location reflect the
 *           latest announcement content in real time, which is critical when
 *           a manager corrects or escalates an announcement.
 */
class AnnouncementUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Announcement  $announcement  The freshly updated announcement
     *                                      model with the new field values.
     */
    public function __construct(public Announcement $announcement)
    {
        //
    }

    /**
     * Broadcast on the private location channel to ensure only authenticated
     * staff at this location receive the update.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->announcement->location_id);
    }

    /**
     * Transmit the complete updated record so the client can merge the
     * changes into the announcements list without an extra API call.
     */
    public function broadcastWith(): array
    {
        return $this->announcement->toArray();
    }
}
