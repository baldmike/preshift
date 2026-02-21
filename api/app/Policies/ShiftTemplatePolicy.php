<?php

namespace App\Policies;

use App\Models\ShiftTemplate;
use App\Models\User;

class ShiftTemplatePolicy
{
    public function update(User $user, ShiftTemplate $shiftTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftTemplate->location_id;
    }

    public function delete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $shiftTemplate->location_id;
    }
}
