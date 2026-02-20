<?php

namespace App\Events;

use App\Models\SwapRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when someone offers to pick up a swap request.
 *
 * Trigger:  Dispatched from SwapRequestController::offer() after setting
 *           picked_up_by and status → "offered".
 * Channel:  Private "location.{id}" — so managers and the original
 *           requester are notified.
 * Payload:  The updated swap request with picker relationship loaded.
 */
class SwapOffered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SwapRequest $swapRequest)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        $locationId = $this->swapRequest->scheduleEntry->schedule->location_id;
        return new PrivateChannel('location.' . $locationId);
    }

    public function broadcastAs(): string
    {
        return 'swap.offered';
    }

    public function broadcastWith(): array
    {
        return $this->swapRequest->load('scheduleEntry', 'requester', 'picker')->toArray();
    }
}
