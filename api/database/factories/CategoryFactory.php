<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->word(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
