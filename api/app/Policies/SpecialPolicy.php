<?php

namespace App\Policies;

use App\Models\Special;
use App\Models\User;

/**
 * Authorization policy for the Special model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify specials belonging to their own location.
 */
class SpecialPolicy
{
    /**
     * Determine whether the user can update the special.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Special  $special
     * @return bool
     */
    public function update(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }

    /**
     * Determine whether the user can delete the special.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Special  $special
     * @return bool
     */
    public function delete(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }

    /**
     * Determine whether the user can decrement the special's quantity.
     *
     * @param  \App\Models\User     $user
     * @param  \App\Models\Special  $special
     * @return bool
     */
    public function decrement(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }
}
