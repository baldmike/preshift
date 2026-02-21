<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function update(User $user, MenuItem $menuItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $menuItem->location_id;
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $menuItem->location_id;
    }
}
