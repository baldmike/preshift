<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftTemplate>
 */
class ShiftTemplateFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 16);

        return [
            'location_id' => Location::factory(),
            'name' => fake()->randomElement(['Lunch', 'Dinner', 'Brunch', 'Happy Hour', 'Close']),
            'start_time' => sprintf('%02d:00', $startHour),
        ];
    }
}
