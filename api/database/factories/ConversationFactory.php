<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Conversation test data.
 *
 * Creates a conversation scoped to a location. Participants should be attached
 * separately after creation via `$conversation->participants()->attach(...)`.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
        ];
    }
}
