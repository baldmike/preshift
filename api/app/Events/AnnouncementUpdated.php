<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Announcement $announcement)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->announcement->location_id);
    }

    public function broadcastWith(): array
    {
        return $this->announcement->toArray();
    }
}
