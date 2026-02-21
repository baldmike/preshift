<?php

namespace App\Policies;

use App\Models\TimeOffRequest;
use App\Models\User;

class TimeOffRequestPolicy
{
    public function approve(User $user, TimeOffRequest $timeOffRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $timeOffRequest->location_id;
    }

    public function deny(User $user, TimeOffRequest $timeOffRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $timeOffRequest->location_id;
    }
}
