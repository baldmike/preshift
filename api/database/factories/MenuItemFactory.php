<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 5, 50),
            'type' => fake()->randomElement(['food', 'drink', 'both']),
            'is_new' => false,
            'is_active' => true,
            'allergens' => null,
        ];
    }
}
