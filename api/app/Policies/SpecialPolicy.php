<?php

namespace App\Policies;

use App\Models\Special;
use App\Models\User;

class SpecialPolicy
{
    public function update(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }

    public function delete(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }

    public function decrement(User $user, Special $special): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $special->location_id;
    }
}
