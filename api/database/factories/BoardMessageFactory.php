<?php

namespace Database\Factories;

use App\Models\BoardMessage;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating BoardMessage test data.
 *
 * State methods:
 *   - reply($parent)   : Makes this message a reply to the given parent post
 *   - managersOnly()   : Sets visibility to 'managers'
 *   - pinned()         : Marks the post as pinned
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardMessage>
 */
class BoardMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BoardMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'visibility' => 'all',
            'pinned' => false,
        ];
    }

    /**
     * Make this message a reply to the given parent post.
     */
    public function reply(BoardMessage $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'location_id' => $parent->location_id,
        ]);
    }

    /**
     * Set visibility to managers-only.
     */
    public function managersOnly(): static
    {
        return $this->state(fn () => [
            'visibility' => 'managers',
        ]);
    }

    /**
     * Mark the post as pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn () => [
            'pinned' => true,
        ]);
    }
}
