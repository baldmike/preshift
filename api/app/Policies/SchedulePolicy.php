<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

/**
 * Authorization policy for the Schedule model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only view, modify, publish, or unpublish schedules
 * belonging to their own location.
 */
class SchedulePolicy
{
    /**
     * Determine whether the user can view the schedule.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Schedule  $schedule
     * @return bool
     */
    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    /**
     * Determine whether the user can update the schedule.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Schedule  $schedule
     * @return bool
     */
    public function update(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    /**
     * Determine whether the user can publish the schedule.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Schedule  $schedule
     * @return bool
     */
    public function publish(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    /**
     * Determine whether the user can unpublish the schedule.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Schedule  $schedule
     * @return bool
     */
    public function unpublish(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }
}
