<?php

namespace App\Policies;

use App\Models\ShiftTemplate;
use App\Models\User;

/**
 * Authorization policy for the ShiftTemplate model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify shift templates belonging to their own location.
 */
class ShiftTemplatePolicy
{
    /**
     * Determine whether the user can update the shift template.
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\ShiftTemplate  $shiftTemplate
     * @return bool
     */
    public function update(User $user, ShiftTemplate $shiftTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftTemplate->location_id;
    }

    /**
     * Determine whether the user can delete the shift template.
     *
     * @param  \App\Models\User           $user
     * @param  \App\Models\ShiftTemplate  $shiftTemplate
     * @return bool
     */
    public function delete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftTemplate->location_id;
    }
}
