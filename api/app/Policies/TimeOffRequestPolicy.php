<?php

namespace App\Policies;

use App\Models\TimeOffRequest;
use App\Models\User;

/**
 * Authorization policy for the TimeOffRequest model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only approve or deny time-off requests belonging
 * to their own location.
 */
class TimeOffRequestPolicy
{
    /**
     * Determine whether the user can approve the time-off request.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\TimeOffRequest  $timeOffRequest
     * @return bool
     */
    public function approve(User $user, TimeOffRequest $timeOffRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $timeOffRequest->location_id;
    }

    /**
     * Determine whether the user can deny the time-off request.
     *
     * @param  \App\Models\User            $user
     * @param  \App\Models\TimeOffRequest  $timeOffRequest
     * @return bool
     */
    public function deny(User $user, TimeOffRequest $timeOffRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $timeOffRequest->location_id;
    }
}
