<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->stateAbbr(),
            'timezone' => fake()->optional()->timezone(),
        ];
    }
}
