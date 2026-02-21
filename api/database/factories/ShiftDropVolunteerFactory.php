<?php

namespace Database\Factories;

use App\Models\ShiftDrop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftDropVolunteer>
 */
class ShiftDropVolunteerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shift_drop_id' => ShiftDrop::factory(),
            'user_id' => User::factory(),
            'selected' => false,
        ];
    }
}
