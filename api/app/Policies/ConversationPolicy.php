<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * Authorization policy for the Conversation model.
 *
 * Ensures that only participants of a conversation can view its messages
 * or send new messages. This prevents users from accessing private DM
 * threads they are not part of.
 */
class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation and its messages.
     * Only participants of the conversation are allowed access.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\Conversation  $conversation
     * @return bool
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }
}
