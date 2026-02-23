<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConversationController manages direct message conversations.
 *
 * Conversations are private 1-on-1 threads between two staff members at the
 * same location. This controller handles listing a user's conversations and
 * the find-or-create logic for starting a new conversation with a colleague.
 */
class ConversationController extends Controller
{
    /**
     * List the authenticated user's conversations.
     *
     * Returns conversations ordered by the most recent message, with
     * participants and latest message eagerly loaded. Each conversation
     * includes an unread_count computed in the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.sender'])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at)
            ->values();

        return response()->json(ConversationResource::collection($conversations));
    }

    /**
     * Find or create a conversation between the authenticated user and target user.
     *
     * If a conversation already exists between the two users, returns it.
     * Otherwise creates a new conversation and attaches both participants.
     * The target user must be at the same location.
     *
     * @param  \App\Http\Requests\StoreConversationRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        $user = $request->user();
        $targetUser = User::findOrFail($request->validated('user_id'));

        // Enforce same-location constraint
        if ($user->location_id !== $targetUser->location_id) {
            return response()->json(['message' => 'Target user must be at the same location.'], 422);
        }

        // Prevent self-conversations
        if ($user->id === $targetUser->id) {
            return response()->json(['message' => 'Cannot create a conversation with yourself.'], 422);
        }

        // Check if a conversation already exists between these two users
        $existing = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereHas('participants', function ($q) use ($targetUser) {
            $q->where('user_id', $targetUser->id);
        })->first();

        if ($existing) {
            $existing->load('participants', 'latestMessage.sender');
            return response()->json(new ConversationResource($existing));
        }

        // Create new conversation
        $conversation = Conversation::create([
            'location_id' => $user->location_id,
        ]);

        $conversation->participants()->attach([
            $user->id => ['last_read_at' => now()],
            $targetUser->id => ['last_read_at' => null],
        ]);

        $conversation->load('participants', 'latestMessage.sender');

        return response()->json(new ConversationResource($conversation), 201);
    }
}
