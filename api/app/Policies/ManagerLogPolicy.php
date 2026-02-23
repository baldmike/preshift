<?php

namespace App\Policies;

use App\Models\ManagerLog;
use App\Models\User;

/**
 * Authorization policy for the ManagerLog model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify logs belonging to their own location.
 */
class ManagerLogPolicy
{
    /**
     * Determine whether the user can update the manager log.
     *
     * @param  \App\Models\User        $user
     * @param  \App\Models\ManagerLog   $managerLog
     * @return bool
     */
    public function update(User $user, ManagerLog $managerLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $managerLog->location_id;
    }

    /**
     * Determine whether the user can delete the manager log.
     *
     * @param  \App\Models\User        $user
     * @param  \App\Models\ManagerLog   $managerLog
     * @return bool
     */
    public function delete(User $user, ManagerLog $managerLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $managerLog->location_id;
    }
}
