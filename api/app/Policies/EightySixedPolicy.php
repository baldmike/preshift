<?php

namespace App\Policies;

use App\Models\EightySixed;
use App\Models\User;

class EightySixedPolicy
{
    public function restore(User $user, EightySixed $eightySixed): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $eightySixed->location_id;
    }
}
