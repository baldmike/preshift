<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    public function publish(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }

    public function unpublish(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $schedule->location_id;
    }
}
