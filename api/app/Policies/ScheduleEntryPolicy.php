<?php

namespace App\Policies;

use App\Models\ScheduleEntry;
use App\Models\User;

class ScheduleEntryPolicy
{
    public function update(User $user, ScheduleEntry $scheduleEntry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $scheduleEntry->schedule->location_id;
    }

    public function delete(User $user, ScheduleEntry $scheduleEntry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $scheduleEntry->schedule->location_id;
    }
}
