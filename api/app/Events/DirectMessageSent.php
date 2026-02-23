<?php

namespace App\Events;

use App\Http\Resources\DirectMessageResource;
use App\Models\DirectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event fired when a direct message is sent in a conversation.
 *
 * Trigger:  Dispatched from DirectMessageController::store() after a user
 *           sends a message.
 * Channel:  Private "user.{recipientId}" -- sent to the OTHER participant's
 *           personal channel to keep DMs private.
 * Payload:  The full DirectMessage resource including sender info and
 *           conversation_id so the recipient can update their conversation.
 * Purpose:  Delivers real-time DM notifications to the recipient without
 *           broadcasting on the location channel (DMs are private).
 */
class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  DirectMessage  $directMessage  The newly sent message.
     * @param  int            $recipientId    The user ID of the other participant.
     */
    public function __construct(
        public DirectMessage $directMessage,
        public int $recipientId
    ) {
        //
    }

    /**
     * Broadcast on the recipient's private user channel.
     * This ensures DMs stay private and are not visible on the location channel.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->recipientId);
    }

    /**
     * Custom broadcast event name for the frontend to listen on.
     */
    public function broadcastAs(): string
    {
        return 'direct-message.sent';
    }

    /**
     * Send the full message resource so the recipient can render it immediately.
     */
    public function broadcastWith(): array
    {
        $this->directMessage->load('sender');

        return (new DirectMessageResource($this->directMessage))->resolve();
    }
}
