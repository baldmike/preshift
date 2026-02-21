<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PushItem>
 */
class PushItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'menu_item_id' => null,
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'reason' => fake()->optional()->sentence(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
