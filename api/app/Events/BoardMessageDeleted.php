<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a board message is deleted.
 *
 * Trigger:  Dispatched from BoardMessageController::destroy() after the
 *           message is removed from the database.
 * Channel:  Private "location.{id}" -- scoped to the message's location.
 * Payload:  The deleted message's id and parent_id so the frontend knows
 *           which post (or reply) to remove from the board.
 * Purpose:  Removes the post from all connected clients' boards in real time.
 */
class BoardMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array  $data  Contains 'id', 'parent_id', and 'location_id'.
     */
    public function __construct(public array $data)
    {
        //
    }

    /**
     * Broadcast on the private location channel.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->data['location_id']);
    }

    /**
     * Custom broadcast event name for the frontend to listen on.
     */
    public function broadcastAs(): string
    {
        return 'board-message.deleted';
    }

    /**
     * Send just the id and parent_id so the frontend can identify which
     * message to remove.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->data['id'],
            'parent_id' => $this->data['parent_id'],
        ];
    }
}
