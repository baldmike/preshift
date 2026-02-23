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
 * Broadcast event fired when an existing board message is updated (body edit,
 * visibility change, or pin/unpin toggle).
 *
 * Trigger:  Dispatched from BoardMessageController::update() and ::pin().
 * Channel:  Private "location.{id}" -- scoped to the message's location.
 * Payload:  The full updated BoardMessage resource.
 * Purpose:  Pushes the updated post so all connected clients reflect the change.
 */
class BoardMessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  BoardMessage  $boardMessage  The updated board message.
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
        return 'board-message.updated';
    }

    /**
     * Send the full board message resource so clients can update in place.
     */
    public function broadcastWith(): array
    {
        $this->boardMessage->load('user', 'replies.user');

        return (new BoardMessageResource($this->boardMessage))->resolve();
    }
}
