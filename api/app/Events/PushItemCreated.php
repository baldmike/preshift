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
 * Broadcast event fired when a new "push item" is created -- a menu item that
 * management wants staff to actively suggest/upsell to guests.
 *
 * Trigger:  Dispatched from PushItemController::store() after a manager or
 *           admin creates a new PushItem record.
 * Channel:  Private "location.{id}" -- scoped to the push item's location.
 * Payload:  The full PushItem model as an array (id, title, description,
 *           reason, priority, menu_item_id, created_by, etc.).
 * Purpose:  Alerts all connected front-end clients at the location in real
 *           time so servers and bartenders immediately see a new item to push.
 */
class PushItemCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  PushItem  $pushItem  The newly created push item record,
     *                              including its priority level and the reason
     *                              management wants it promoted.
     */
    public function __construct(public PushItem $pushItem)
    {
        //
    }

    /**
     * Broadcast on the private location channel so only staff at the relevant
     * location receive the push item notification.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->pushItem->location_id);
    }

    /**
     * Send the full PushItem record so the client can display it on the push
     * items board without an additional API call.
     */
    public function broadcastWith(): array
    {
        return $this->pushItem->toArray();
    }
}
