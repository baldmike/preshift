<?php

namespace App\Policies;

use App\Models\BoardMessage;
use App\Models\User;

/**
 * Authorization policy for the BoardMessage model.
 *
 * Controls who can view, update, delete, and pin board messages.
 * Staff members (server/bartender) can only see posts with visibility 'all'.
 * Only the post author or an admin/manager at the same location can edit or
 * delete posts. Only admins and managers can pin/unpin posts.
 */
class BoardMessagePolicy
{
    /**
     * Determine whether the user can view the board message.
     * Staff can only see posts with visibility 'all'; managers/admins see everything.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return bool
     */
    public function view(User $user, BoardMessage $boardMessage): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->location_id !== $boardMessage->location_id) {
            return false;
        }

        if ($boardMessage->visibility === 'managers' && $user->isStaff()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the board message.
     * Allowed for the original author, or any admin/manager at the same location.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return bool
     */
    public function update(User $user, BoardMessage $boardMessage): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->location_id !== $boardMessage->location_id) {
            return false;
        }

        return $user->id === $boardMessage->user_id || $user->isManager();
    }

    /**
     * Determine whether the user can delete the board message.
     * Same rules as update: original author or admin/manager at same location.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return bool
     */
    public function delete(User $user, BoardMessage $boardMessage): bool
    {
        return $this->update($user, $boardMessage);
    }

    /**
     * Determine whether the user can pin/unpin the board message.
     * Only admins and managers at the same location can pin posts.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return bool
     */
    public function pin(User $user, BoardMessage $boardMessage): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $boardMessage->location_id && $user->isManager();
    }
}
