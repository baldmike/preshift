<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a user acknowledges a pre-shift item.
 *
 * Dispatched on the location's private WebSocket channel as
 * "acknowledgment.recorded" so manager UIs can update the red
 * unacknowledged indicator in real time.
 */
class AcknowledgmentRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public int $acknowledgedCount;
    public int $totalItems;
    public int $percentage;
    private int $locationId;

    public function __construct(int $locationId, int $userId, int $acknowledgedCount, int $totalItems, int $percentage)
    {
        $this->locationId = $locationId;
        $this->userId = $userId;
        $this->acknowledgedCount = $acknowledgedCount;
        $this->totalItems = $totalItems;
        $this->percentage = $percentage;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->locationId);
    }

    public function broadcastAs(): string
    {
        return 'acknowledgment.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'acknowledged_count' => $this->acknowledgedCount,
            'total_items' => $this->totalItems,
            'percentage' => $this->percentage,
        ];
    }
}
