<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'event_date' => now()->toDateString(),
            'event_time' => fake()->time('H:i'),
            'created_by' => User::factory(),
        ];
    }
}
