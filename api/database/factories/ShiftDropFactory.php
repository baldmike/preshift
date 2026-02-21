<?php

namespace Database\Factories;

use App\Models\ScheduleEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftDrop>
 */
class ShiftDropFactory extends Factory
{
    public function definition(): array
    {
        return [
            'schedule_entry_id' => ScheduleEntry::factory(),
            'requested_by' => User::factory(),
            'reason' => fake()->optional()->sentence(),
            'status' => 'open',
            'filled_by' => null,
            'filled_at' => null,
        ];
    }

    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'filled',
            'filled_by' => User::factory(),
            'filled_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
