<?php

namespace App\Policies;

use App\Models\ScheduleEntry;
use App\Models\User;

/**
 * Authorization policy for the ScheduleEntry model.
 *
 * Enforces model-level location ownership via the parent schedule relationship.
 * Admin users always pass. Non-admin users may only modify schedule entries
 * whose parent schedule belongs to their own location.
 */
class ScheduleEntryPolicy
{
    /**
     * Determine whether the user can update the schedule entry.
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\ScheduleEntry  $scheduleEntry
     * @return bool
     */
    public function update(User $user, ScheduleEntry $scheduleEntry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $scheduleEntry->schedule->location_id;
    }

    /**
     * Determine whether the user can delete the schedule entry.
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\ScheduleEntry  $scheduleEntry
     * @return bool
     */
    public function delete(User $user, ScheduleEntry $scheduleEntry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $scheduleEntry->schedule->location_id;
    }
}
