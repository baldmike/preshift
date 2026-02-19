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
 * Broadcast event fired when a previously 86'd item is restored -- i.e., the
 * item is available again for service.
 *
 * Trigger:  Dispatched from EightySixedController::restore() after a manager
 *           or admin sets the `restored_at` timestamp on an EightySixed record.
 * Channel:  Private "location.{id}" -- scoped to the item's location.
 * Payload:  The full EightySixed model as an array (now including the
 *           `restored_at` timestamp that marks it as back in stock).
 * Purpose:  Sends a real-time WebSocket notification so every connected client
 *           at the location can immediately remove (or visually strike-through)
 *           the item from their 86'd board.
 */
class ItemRestored implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  EightySixed  $item  The restored 86'd record. Its `restored_at`
     *                             field will now be populated, distinguishing
     *                             it from still-active 86'd items.
     */
    public function __construct(public EightySixed $item)
    {
        //
    }

    /**
     * Broadcast on the private location channel so only authenticated staff
     * at this location receive the restoration notice.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->item->location_id);
    }

    /**
     * Include the full record (with `restored_at` set) so the client can
     * update its local state without an additional API request.
     */
    public function broadcastWith(): array
    {
        return $this->item->toArray();
    }
}
