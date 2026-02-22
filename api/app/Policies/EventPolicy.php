<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Authorization policy for the Event model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify events belonging to their own location.
 */
class EventPolicy
{
    /**
     * Determine whether the user can update the event.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $event->location_id;
    }

    /**
     * Determine whether the user can delete the event.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function delete(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $event->location_id;
    }
}
