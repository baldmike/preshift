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
 * Broadcast event fired when a new daily/weekly/monthly/limited-time special
 * is created for a location.
 *
 * Trigger:  Dispatched from SpecialController::store() after a manager or
 *           admin persists a new Special record.
 * Channel:  Private "location.{id}" -- scoped to the special's location.
 * Payload:  The full Special model as an array (id, title, description, type,
 *           starts_at, ends_at, menu_item_id, created_by, etc.).
 * Purpose:  Pushes a real-time WebSocket update so all connected staff at the
 *           location see the new special appear on the specials board
 *           immediately, without requiring a page refresh.
 */
class SpecialCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Special  $special  The newly created special, including its
     *                            title, description, date range, and linked
     *                            menu item (if any).
     */
    public function __construct(public Special $special)
    {
        //
    }

    /**
     * Broadcast on the private location channel so only staff authenticated
     * for this location receive the new special.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->special->location_id);
    }

    /**
     * Send the entire Special record so clients can render the new special
     * without a follow-up fetch.
     */
    public function broadcastWith(): array
    {
        return $this->special->toArray();
    }
}
