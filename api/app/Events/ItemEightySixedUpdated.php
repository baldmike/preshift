<?php

namespace App\Events;

use App\Models\EightySixed;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when an existing 86'd item is edited (e.g., its
 * name, reason, or linked menu item changes).
 *
 * Trigger:  Dispatched from EightySixedController::update() after the
 *           EightySixed record is saved with new values.
 * Channel:  Private "location.{id}" -- scoped to the item's location.
 * Payload:  The full updated EightySixed model as an array so clients can
 *           replace the stale version in their local state.
 * Purpose:  Ensures connected front-end clients at the location reflect the
 *           latest 86'd item content in real time.
 */
class ItemEightySixedUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  EightySixed  $item  The freshly updated 86'd record with the
     *                             new field values.
     */
    public function __construct(public EightySixed $item)
    {
        //
    }

    /**
     * Broadcast on the private location channel to ensure only authenticated
     * staff at this location receive the update.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->item->location_id);
    }

    /**
     * Transmit the complete updated record so the client can merge the
     * changes into the 86'd list without an extra API call.
     */
    public function broadcastWith(): array
    {
        return $this->item->toArray();
    }
}
