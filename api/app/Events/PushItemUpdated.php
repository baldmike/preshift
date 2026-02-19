<?php

namespace App\Events;

use App\Models\PushItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when an existing push item is updated (e.g., its
 * priority, description, reason, or active status changes).
 *
 * Trigger:  Dispatched from PushItemController::update() after the PushItem
 *           record is saved with new values.
 * Channel:  Private "location.{id}" -- scoped to the push item's location.
 * Payload:  The full updated PushItem model as an array so clients can replace
 *           the stale version in their local state.
 * Purpose:  Keeps all connected clients at the location in sync with the
 *           latest push item details via WebSocket, ensuring staff always see
 *           up-to-date priorities and descriptions.
 */
class PushItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  PushItem  $pushItem  The freshly updated push item containing
     *                              the new field values.
     */
    public function __construct(public PushItem $pushItem)
    {
        //
    }

    /**
     * Broadcast on the private location channel to ensure only authorized
     * staff at this location receive the update.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->pushItem->location_id);
    }

    /**
     * Include the complete updated record so clients can merge changes
     * without issuing a separate GET request.
     */
    public function broadcastWith(): array
    {
        return $this->pushItem->toArray();
    }
}
