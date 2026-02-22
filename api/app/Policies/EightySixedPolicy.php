<?php

namespace App\Policies;

use App\Models\EightySixed;
use App\Models\User;

/**
 * Authorization policy for the EightySixed model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only update or restore 86'd items belonging to their
 * own location.
 */
class EightySixedPolicy
{
    /**
     * Determine whether the user can update the 86'd item.
     *
     * @param  \App\Models\User         $user
     * @param  \App\Models\EightySixed  $eightySixed
     * @return bool
     */
    public function update(User $user, EightySixed $eightySixed): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $eightySixed->location_id;
    }

    /**
     * Determine whether the user can restore the 86'd item.
     *
     * @param  \App\Models\User         $user
     * @param  \App\Models\EightySixed  $eightySixed
     * @return bool
     */
    public function restore(User $user, EightySixed $eightySixed): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $eightySixed->location_id;
    }
}
