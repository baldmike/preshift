<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorization policy for the User model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify or delete users belonging to their
 * own location.
 */
class UserPolicy
{
    /**
     * Determine whether the authenticated user can update the target user.
     *
     * @param  \App\Models\User  $authUser
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->location_id === $targetUser->location_id;
    }

    /**
     * Determine whether the authenticated user can delete the target user.
     *
     * @param  \App\Models\User  $authUser
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->location_id === $targetUser->location_id;
    }
}
