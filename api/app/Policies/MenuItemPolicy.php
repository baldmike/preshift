<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

/**
 * Authorization policy for the MenuItem model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify menu items belonging to their own location.
 */
class MenuItemPolicy
{
    /**
     * Determine whether the user can update the menu item.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\MenuItem  $menuItem
     * @return bool
     */
    public function update(User $user, MenuItem $menuItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $menuItem->location_id;
    }

    /**
     * Determine whether the user can delete the menu item.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\MenuItem  $menuItem
     * @return bool
     */
    public function delete(User $user, MenuItem $menuItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $menuItem->location_id;
    }
}
