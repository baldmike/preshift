<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating DirectMessage test data.
 *
 * Creates a direct message belonging to a conversation with a sender.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DirectMessage>
 */
class DirectMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = DirectMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
