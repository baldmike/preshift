<?php

namespace App\Events;

use App\Models\TimeOffRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a manager approves or denies a time-off request.
 *
 * Trigger:  Dispatched from TimeOffRequestController::approve() or deny()
 *           after updating the request status.
 * Channel:  Private "location.{id}" — notifies the requesting staff member
 *           and updates manager dashboards.
 * Payload:  The resolved time-off request with user and resolver loaded.
 */
class TimeOffResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TimeOffRequest $timeOffRequest)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->timeOffRequest->location_id);
    }

    public function broadcastAs(): string
    {
        return 'time-off.resolved';
    }

    public function broadcastWith(): array
    {
        return $this->timeOffRequest->load('user', 'resolver')->toArray();
    }
}
