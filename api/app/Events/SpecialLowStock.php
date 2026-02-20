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
 * Broadcast event fired when a special's remaining quantity drops to a low threshold.
 *
 * This event is dispatched on the location's private WebSocket channel
 * (e.g., "location.{id}") as "special.low-stock" when the quantity reaches 2,
 * alerting staff that the special is nearly sold out. The broadcast payload
 * includes the special's ID, title, and current quantity.
 */
class SpecialLowStock implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Special $special)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->special->location_id);
    }

    public function broadcastAs(): string
    {
        return 'special.low-stock';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->special->id,
            'title' => $this->special->title,
            'quantity' => $this->special->quantity,
        ];
    }
}
