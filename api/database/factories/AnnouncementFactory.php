<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'priority' => fake()->randomElement(['normal', 'important', 'urgent']),
            'target_roles' => null,
            'posted_by' => User::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }
}
