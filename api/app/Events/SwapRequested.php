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
 * Broadcast when a staff member requests a shift swap.
 *
 * Trigger:  Dispatched from SwapRequestController::store() after creating
 *           the swap request record.
 * Channel:  Private "location.{id}" — derived from the schedule entry's
 *           schedule → location chain.
 * Payload:  The swap request with its schedule entry and requester loaded
 *           so managers see the new request in real time.
 */
class SwapRequested implements ShouldBroadcast
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
        return 'swap.requested';
    }

    public function broadcastWith(): array
    {
        return $this->swapRequest->load('scheduleEntry', 'requester')->toArray();
    }
}
