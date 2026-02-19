<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when an announcement is deleted from a location.
 *
 * Trigger:  Dispatched from AnnouncementController::destroy() after the
 *           Announcement record is removed from the database.
 * Channel:  Private "location.{id}" -- scoped to the deleted announcement's
 *           location.
 * Payload:  A plain array containing at least `id` and `location_id` (captured
 *           before deletion) so the client knows which announcement to remove.
 * Purpose:  Notifies all connected clients at the location to remove the
 *           announcement from their UI in real time.
 *
 * Note:     Accepts a raw array instead of an Eloquent model because the
 *           record has already been deleted before dispatch. The controller
 *           captures the needed identifiers into an array prior to deletion
 *           to prevent serialization errors on the queue worker.
 */
class AnnouncementDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array  $data  An associative array with at minimum `id` and
     *                       `location_id` keys, identifying the announcement
     *                       that was just removed.
     */
    public function __construct(public array $data)
    {
        //
    }

    /**
     * Broadcast on the private location channel derived from the captured
     * location_id in the data array.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->data['location_id']);
    }

    /**
     * Send the captured data array so the client can identify and remove the
     * deleted announcement from the list.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
