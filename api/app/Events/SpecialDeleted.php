<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a special is deleted from a location.
 *
 * Trigger:  Dispatched from SpecialController::destroy() after the Special
 *           record is removed from the database.
 * Channel:  Private "location.{id}" -- scoped to the deleted special's
 *           location.
 * Payload:  A plain array containing at least `id` and `location_id` (captured
 *           before deletion) so the client knows which special to remove.
 * Purpose:  Notifies all connected clients at the location to remove the
 *           special from their UI in real time.
 *
 * Note:     Unlike the Created/Updated events, this event accepts a raw array
 *           instead of an Eloquent model because the record has already been
 *           deleted by the time the event is dispatched. Serializing a deleted
 *           model would fail on the queue worker, so the controller captures
 *           the necessary data into an array before deleting.
 */
class SpecialDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array  $data  An associative array with at minimum `id` and
     *                       `location_id` keys, representing the special that
     *                       was just removed.
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
     * Send the captured data array (id, location_id, etc.) so the client
     * can identify and remove the deleted special from the list.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
