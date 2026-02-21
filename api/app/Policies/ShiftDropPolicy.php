<?php

namespace App\Policies;

use App\Models\ShiftDrop;
use App\Models\User;

/**
 * Authorization policy for the ShiftDrop model.
 *
 * Enforces ownership and location-based authorization for shift drop actions.
 * Cancellation is restricted to the user who originally requested the drop.
 * Volunteering and selection require location ownership via the parent schedule
 * entry's schedule, with admin users always passing.
 */
class ShiftDropPolicy
{
    /**
     * Determine whether the user can cancel the shift drop request.
     *
     * Only the user who originally requested the drop may cancel it.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\ShiftDrop  $shiftDrop
     * @return bool
     */
    public function cancel(User $user, ShiftDrop $shiftDrop): bool
    {
        return $user->id === $shiftDrop->requested_by;
    }

    /**
     * Determine whether the user can volunteer to pick up the dropped shift.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\ShiftDrop  $shiftDrop
     * @return bool
     */
    public function volunteer(User $user, ShiftDrop $shiftDrop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftDrop->scheduleEntry->schedule->location_id;
    }

    /**
     * Determine whether the user can select a volunteer for the dropped shift.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\ShiftDrop  $shiftDrop
     * @return bool
     */
    public function select(User $user, ShiftDrop $shiftDrop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftDrop->scheduleEntry->schedule->location_id;
    }
}
