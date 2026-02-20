<?php

namespace App\Events;

use App\Models\ShiftDrop;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftDropFilled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ShiftDrop $shiftDrop)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        $locationId = $this->shiftDrop->scheduleEntry->schedule->location_id;
        return new PrivateChannel('location.' . $locationId);
    }

    public function broadcastAs(): string
    {
        return 'shift-drop.filled';
    }

    public function broadcastWith(): array
    {
        return $this->shiftDrop->load('scheduleEntry.shiftTemplate', 'requester', 'filler', 'volunteers.user')->toArray();
    }
}
