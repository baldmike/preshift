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
 * Broadcast event fired when a new daily event is created for a location.
 *
 * Trigger:  Dispatched from EventController::store() after a manager or
 *           admin persists a new Event record.
 * Channel:  Private "location.{id}" -- scoped to the event's location.
 * Payload:  The full Event model as an array (id, title, description,
 *           event_date, event_time, created_by, etc.).
 * Purpose:  Pushes a real-time WebSocket update so all connected staff at the
 *           location see the new event appear on the dashboard immediately.
 */
class EventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Event  $event  The newly created event, including its
     *                        title, description, date, and time.
     */
    public function __construct(public Event $event)
    {
        //
    }

    /**
     * Broadcast on the private location channel so only staff authenticated
     * for this location receive the new event.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->event->location_id);
    }

    /**
     * Send the entire Event record so clients can render the new event
     * without a follow-up fetch.
     */
    public function broadcastWith(): array
    {
        return $this->event->toArray();
    }
}
