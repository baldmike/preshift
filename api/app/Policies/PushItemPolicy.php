<?php

namespace App\Policies;

use App\Models\PushItem;
use App\Models\User;

/**
 * Authorization policy for the PushItem model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify push items belonging to their own location.
 */
class PushItemPolicy
{
    /**
     * Determine whether the user can update the push item.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\PushItem  $pushItem
     * @return bool
     */
    public function update(User $user, PushItem $pushItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $pushItem->location_id;
    }

    /**
     * Determine whether the user can delete the push item.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\PushItem  $pushItem
     * @return bool
     */
    public function delete(User $user, PushItem $pushItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $pushItem->location_id;
    }
}
