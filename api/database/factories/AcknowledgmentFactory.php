<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Acknowledgment>
 */
class AcknowledgmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'acknowledgable_type' => Announcement::class,
            'acknowledgable_id' => Announcement::factory(),
            'acknowledged_at' => now(),
        ];
    }
}
