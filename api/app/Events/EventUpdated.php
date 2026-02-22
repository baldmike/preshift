<?php

namespace App\Events;

use App\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when an existing daily event is updated.
 *
 * Trigger:  Dispatched from EventController::update() after the Event
 *           record is saved with new values.
 * Channel:  Private "location.{id}" -- scoped to the event's location.
 * Payload:  The full updated Event model as an array so clients can replace
 *           the stale version in their local state.
 * Purpose:  Ensures every connected front-end client at the location reflects
 *           the latest event details in real time via WebSocket.
 */
class EventUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Event  $event  The freshly updated Event model containing
     *                        the new field values.
     */
    public function __construct(public Event $event)
    {
        //
    }

    /**
     * Broadcast on the private location channel to restrict the update to
     * users who belong to (or administer) this location.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->event->location_id);
    }

    /**
     * Transmit the full updated record so clients can merge the changes into
     * their local events list without an extra API call.
     */
    public function broadcastWith(): array
    {
        return $this->event->toArray();
    }
}
