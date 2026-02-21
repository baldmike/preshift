<?php

namespace App\Policies;

use App\Models\PushItem;
use App\Models\User;

class PushItemPolicy
{
    public function update(User $user, PushItem $pushItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $pushItem->location_id;
    }

    public function delete(User $user, PushItem $pushItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $pushItem->location_id;
    }
}
