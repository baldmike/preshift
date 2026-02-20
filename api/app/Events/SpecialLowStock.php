<?php

namespace App\Events;

use App\Models\Special;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
