<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EightySixed>
 */
class EightySixedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'menu_item_id' => null,
            'item_name' => fake()->words(2, true),
            'reason' => fake()->optional()->sentence(),
            'eighty_sixed_by' => User::factory(),
            'restored_at' => null,
        ];
    }

    public function restored(): static
    {
        return $this->state(fn (array $attributes) => [
            'restored_at' => now(),
        ]);
    }
}
