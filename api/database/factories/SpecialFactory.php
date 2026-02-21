<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Special>
 */
class SpecialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'menu_item_id' => null,
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['daily', 'weekly', 'monthly', 'limited_time']),
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'quantity' => null,
            'created_by' => User::factory(),
        ];
    }
}
