<?php

namespace App\Policies;

use App\Models\ShiftDrop;
use App\Models\User;

class ShiftDropPolicy
{
    public function cancel(User $user, ShiftDrop $shiftDrop): bool
    {
        return $user->id === $shiftDrop->requested_by;
    }

    public function volunteer(User $user, ShiftDrop $shiftDrop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftDrop->scheduleEntry->schedule->location_id;
    }

    public function select(User $user, ShiftDrop $shiftDrop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftDrop->scheduleEntry->schedule->location_id;
    }
}
