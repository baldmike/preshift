<?php

namespace Tests\Feature;

use App\Events\DirectMessageSent;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for DirectMessageController endpoints.
 *
 * Verifies:
 *   - Unauthenticated user cannot access message endpoints
 *   - Non-participant cannot send messages
 *   - Message body validation (required, max length)
 *   - Sending a message broadcasts DirectMessageSent event
 *   - Unread count ignores messages sent by the authenticated user
 *   - Unread count returns zero when all messages are read
 */
class DirectMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location, two users, and a conversation between them.
     */
    private function seedConversation(): array
    {
        $location = Location::create([
            'name' => 'DM Test Location',
            'address' => '100 Test St',
            'timezone' => 'America/New_York',
        ]);

        $userA = User::create([
            'name' => 'Sender',
            'email' => 'sender@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        $userB = User::create([
            'name' => 'Receiver',
            'email' => 'receiver@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        $conversation = Conversation::create(['location_id' => $location->id]);
        $conversation->participants()->attach([
            $userA->id => ['last_read_at' => now()],
            $userB->id => ['last_read_at' => now()],
        ]);

        return compact('location', 'userA', 'userB', 'conversation');
    }

    // ══════════════════════════════════════════════
    //  AUTH GUARD
    // ══════════════════════════════════════════════

    /**
     * Unauthenticated requests to message endpoints should return 401.
     */
    public function test_unauthenticated_cannot_access_messages(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        $this->getJson("/api/conversations/{$seed['conversation']->id}/messages")
            ->assertUnauthorized();

        $this->postJson("/api/conversations/{$seed['conversation']->id}/messages", ['body' => 'Hi'])
            ->assertUnauthorized();

        $this->getJson('/api/conversations/unread-count')
            ->assertUnauthorized();
    }

    // ══════════════════════════════════════════════
    //  NON-PARTICIPANT CANNOT SEND
    // ══════════════════════════════════════════════

    /**
     * A user who is not a participant in the conversation cannot send messages.
     */
    public function test_non_participant_cannot_send_message(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        $response = $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/conversations/{$seed['conversation']->id}/messages", [
                'body' => 'Sneaky message',
            ]);

        $response->assertForbidden();
    }

    // ══════════════════════════════════════════════
    //  VALIDATION
    // ══════════════════════════════════════════════

    /**
     * Message body is required — empty body returns 422.
     */
    public function test_message_body_is_required(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson("/api/conversations/{$seed['conversation']->id}/messages", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    /**
     * Message body cannot exceed 2000 characters.
     */
    public function test_message_body_max_length(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson("/api/conversations/{$seed['conversation']->id}/messages", [
                'body' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    // ══════════════════════════════════════════════
    //  BROADCAST EVENT
    // ══════════════════════════════════════════════

    /**
     * Sending a message dispatches a DirectMessageSent broadcast event.
     */
    public function test_sending_message_broadcasts_event(): void
    {
        Event::fake([DirectMessageSent::class]);
        $seed = $this->seedConversation();

        $this->actingAs($seed['userA'], 'sanctum')
            ->postJson("/api/conversations/{$seed['conversation']->id}/messages", [
                'body' => 'Broadcast test',
            ]);

        Event::assertDispatched(DirectMessageSent::class);
    }

    // ══════════════════════════════════════════════
    //  UNREAD COUNT — OWN MESSAGES IGNORED
    // ══════════════════════════════════════════════

    /**
     * Unread count should not include messages the user sent themselves.
     */
    public function test_unread_count_ignores_own_messages(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        // userA sends a message — this should NOT count as unread for userA
        DirectMessage::create([
            'conversation_id' => $seed['conversation']->id,
            'sender_id' => $seed['userA']->id,
            'body' => 'My own message',
        ]);

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->getJson('/api/conversations/unread-count');

        $response->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    // ══════════════════════════════════════════════
    //  UNREAD COUNT — ZERO WHEN READ
    // ══════════════════════════════════════════════

    /**
     * After fetching messages (which updates last_read_at), unread count
     * should return zero.
     */
    public function test_unread_count_zero_after_reading(): void
    {
        Event::fake();
        $seed = $this->seedConversation();

        // Set last_read_at to the past so the new message counts as unread
        $seed['conversation']->participants()->updateExistingPivot($seed['userA']->id, [
            'last_read_at' => now()->subHour(),
        ]);

        DirectMessage::create([
            'conversation_id' => $seed['conversation']->id,
            'sender_id' => $seed['userB']->id,
            'body' => 'Unread message',
        ]);

        // Fetch messages — triggers last_read_at update
        $this->actingAs($seed['userA'], 'sanctum')
            ->getJson("/api/conversations/{$seed['conversation']->id}/messages");

        // Now unread count should be zero
        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->getJson('/api/conversations/unread-count');

        $response->assertOk()
            ->assertJsonPath('unread_count', 0);
    }
}
