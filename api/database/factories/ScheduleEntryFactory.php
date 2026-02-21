<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduleEntry>
 */
class ScheduleEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'user_id' => User::factory(),
            'shift_template_id' => ShiftTemplate::factory(),
            'date' => now()->toDateString(),
            'role' => fake()->randomElement(['server', 'bartender']),
            'notes' => null,
        ];
    }
}
