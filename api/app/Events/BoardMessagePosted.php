<?php

namespace App\Events;

use App\Http\Resources\BoardMessageResource;
use App\Models\BoardMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a new board message (post or reply) is created.
 *
 * Trigger:  Dispatched from BoardMessageController::store() after a user
 *           creates a new board message.
 * Channel:  Private "location.{id}" -- scoped to the message's location.
 * Payload:  The full BoardMessage resource including author info and replies.
 * Purpose:  Pushes a real-time WebSocket update so all connected staff at the
 *           location see the new post appear on the message board immediately.
 */
class BoardMessagePosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  BoardMessage  $boardMessage  The newly created board message.
     */
    public function __construct(public BoardMessage $boardMessage)
    {
        //
    }

    /**
     * Broadcast on the private location channel.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('location.' . $this->boardMessage->location_id);
    }

    /**
     * Custom broadcast event name for the frontend to listen on.
     */
    public function broadcastAs(): string
    {
        return 'board-message.posted';
    }

    /**
     * Send the full board message resource so clients can render without re-fetching.
     */
    public function broadcastWith(): array
    {
        $this->boardMessage->load('user', 'replies.user');

        return (new BoardMessageResource($this->boardMessage))->resolve();
    }
}
