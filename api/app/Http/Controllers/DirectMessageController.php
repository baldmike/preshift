<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Http\Requests\StoreDirectMessageRequest;
use App\Http\Resources\DirectMessageResource;
use App\Models\Conversation;
use App\Models\DirectMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DirectMessageController manages messages within a conversation.
 *
 * Handles fetching the message history for a conversation, sending new
 * messages, and providing a total unread conversation count. Messages are
 * broadcast in real time on the recipient's private user channel so DMs
 * stay private and are not visible on the location channel.
 */
class DirectMessageController extends Controller
{
    /**
     * List all messages in a conversation.
     *
     * Authorizes via ConversationPolicy::view to ensure only participants
     * can read messages. As a side effect, updates the authenticated user's
     * last_read_at timestamp to mark the conversation as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Conversation  $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->directMessages()
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        // Mark as read: update the authenticated user's last_read_at
        $conversation->participants()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(DirectMessageResource::collection($messages));
    }

    /**
     * Send a new direct message in a conversation.
     *
     * Authorizes via ConversationPolicy::view, creates the message, updates
     * the sender's last_read_at, and broadcasts to the other participant's
     * private user channel.
     *
     * @param  \App\Http\Requests\StoreDirectMessageRequest  $request
     * @param  \App\Models\Conversation                      $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDirectMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        // Update sender's last_read_at so their own messages don't count as unread
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $message->load('sender');

        // Broadcast to the OTHER participant's user channel
        $recipientId = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->first()
            ->id;

        broadcast(new DirectMessageSent($message, $recipientId))->toOthers();

        return response()->json(new DirectMessageResource($message), 201);
    }

    /**
     * Get the total number of conversations with unread messages.
     *
     * Returns a count of conversations where the authenticated user has
     * unread messages (messages created after their last_read_at).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $user->conversations()
            ->get()
            ->filter(function ($conversation) use ($user) {
                $lastReadAt = $conversation->pivot->last_read_at;

                $query = $conversation->directMessages()
                    ->where('sender_id', '!=', $user->id);

                if ($lastReadAt) {
                    $query->where('created_at', '>', $lastReadAt);
                }

                return $query->exists();
            })
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
