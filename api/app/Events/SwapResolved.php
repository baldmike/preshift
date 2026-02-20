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
 * Broadcast when a manager approves or denies a swap request.
 *
 * Trigger:  Dispatched from SwapRequestController::approve() or deny()
 *           after updating the swap request status.
 * Channel:  Private "location.{id}" — notifies both the requester and
 *           the person who offered to pick up the shift.
 * Payload:  The resolved swap request with all relationships loaded.
 */
class SwapResolved implements ShouldBroadcast
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
        return 'swap.resolved';
    }

    public function broadcastWith(): array
    {
        return $this->swapRequest->load('scheduleEntry', 'requester', 'picker', 'resolver')->toArray();
    }
}
