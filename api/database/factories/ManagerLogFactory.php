<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManagerLog>
 */
class ManagerLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'created_by' => User::factory(),
            'log_date' => now()->toDateString(),
            'body' => fake()->paragraphs(2, true),
            'weather_snapshot' => [
                'current' => [
                    'temperature' => 72,
                    'feels_like' => 70,
                    'humidity' => 45,
                    'wind_speed' => 8,
                    'weather_code' => 0,
                    'description' => 'Clear Sky',
                ],
                'today' => [
                    'high' => 78,
                    'low' => 62,
                    'weather_code' => 0,
                    'description' => 'Clear Sky',
                ],
            ],
            'events_snapshot' => [],
            'schedule_snapshot' => [],
        ];
    }
}
