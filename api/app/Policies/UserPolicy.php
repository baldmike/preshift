<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->location_id === $targetUser->location_id;
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->location_id === $targetUser->location_id;
    }
}
