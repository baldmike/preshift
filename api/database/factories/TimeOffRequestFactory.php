<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeOffRequest>
 */
class TimeOffRequestFactory extends Factory
{
    public function definition(): array
    {
        $startDate = now()->addDays(fake()->numberBetween(1, 14));

        return [
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->addDays(fake()->numberBetween(0, 3))->toDateString(),
            'reason' => fake()->optional()->sentence(),
            'status' => 'pending',
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }

    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'denied',
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
