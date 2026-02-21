<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/**
 * Authorization policy for the Announcement model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify announcements belonging to their own location.
 */
class AnnouncementPolicy
{
    /**
     * Determine whether the user can update the announcement.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\Announcement  $announcement
     * @return bool
     */
    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $announcement->location_id;
    }

    /**
     * Determine whether the user can delete the announcement.
     *
     * @param  \App\Models\User          $user
     * @param  \App\Models\Announcement  $announcement
     * @return bool
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $announcement->location_id;
    }
}
