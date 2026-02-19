<?php

namespace App\Events;

use App\Models\Special;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when an existing special is updated (e.g., its title,
 * description, date range, or active status changes).
 *
 * Trigger:  Dispatched from SpecialController::update() after the Special
 *           record is saved with new values.
 * Channel:  Private "location.{id}" -- scoped to the special's location.
 * Payload:  The full updated Special model as an array so clients can replace
 *           the stale version in their local state.
 * Purpose:  Ensures every connected front-end client at the location reflects
 *           the latest special details in real time via WebSocket.
 */
class SpecialUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Special  $special  The freshly updated Special model containing
     *                            the new field values.
     */
    public function __construct(public Special $special)
    {
        //
    }

    /**
     * Broadcast on the private location channel to restrict the update to
     * users who belong to (or administer) this location.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->special->location_id);
    }

    /**
     * Transmit the full updated record so clients can merge the changes into
     * their local specials list without an extra API call.
     */
    public function broadcastWith(): array
    {
        return $this->special->toArray();
    }
}
