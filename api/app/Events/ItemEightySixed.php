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
 * Broadcast event fired when a menu item (or free-text item) is 86'd -- i.e.,
 * marked as unavailable for the current service period.
 *
 * Trigger:  Dispatched from EightySixedController::store() after a manager or
 *           admin creates a new EightySixed record.
 * Channel:  Private "location.{id}" -- only users authenticated for that
 *           location will receive the message.
 * Payload:  The full EightySixed model as an array (id, location_id,
 *           menu_item_id, item_name, reason, eighty_sixed_by, timestamps).
 * Purpose:  Pushes a real-time WebSocket notification to every connected
 *           front-end client at the same location so the 86'd board updates
 *           instantly without polling or manual refresh.
 */
class ItemEightySixed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  EightySixed  $item  The newly created 86'd record, including the
     *                             item name, optional reason, and the user who
     *                             flagged it.
     */
    public function __construct(public EightySixed $item)
    {
        //
    }

    /**
     * Broadcast on the private channel scoped to the item's location so that
     * only staff members belonging to that location receive the update.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->item->location_id);
    }

    /**
     * Send the entire EightySixed record as the broadcast payload so the
     * client can append it to the 86'd list without a follow-up API call.
     */
    public function broadcastWith(): array
    {
        return $this->item->toArray();
    }
}
