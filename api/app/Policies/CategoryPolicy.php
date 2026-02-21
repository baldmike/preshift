<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, Category $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $category->location_id;
    }

    public function delete(User $user, Category $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $category->location_id;
    }
}
