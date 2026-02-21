<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Authorization policy for the Category model.
 *
 * Enforces model-level location ownership. Admin users always pass.
 * Non-admin users may only modify categories belonging to their own location.
 */
class CategoryPolicy
{
    /**
     * Determine whether the user can update the category.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Category  $category
     * @return bool
     */
    public function update(User $user, Category $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $category->location_id;
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param  \App\Models\User      $user
     * @param  \App\Models\Category  $category
     * @return bool
     */
    public function delete(User $user, Category $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->location_id === $category->location_id;
    }
}
