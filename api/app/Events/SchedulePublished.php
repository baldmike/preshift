<?php

namespace App\Events;

use App\Models\Schedule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a manager publishes a weekly schedule.
 *
 * Trigger:  Dispatched from ScheduleController::publish() after setting
 *           the schedule status to "published".
 * Channel:  Private "location.{id}" — scoped to the schedule's location.
 * Payload:  The schedule record (id, week_start, status, published_at)
 *           so staff clients can refresh their shift views.
 */
class SchedulePublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Schedule $schedule)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->schedule->location_id);
    }

    public function broadcastAs(): string
    {
        return 'schedule.published';
    }

    public function broadcastWith(): array
    {
        return $this->schedule->toArray();
    }
}
