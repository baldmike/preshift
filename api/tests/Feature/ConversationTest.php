<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for the Conversation and DirectMessage API endpoints.
 *
 * Verifies:
 *   - Listing conversations
 *   - Find-or-create (returns existing conversation)
 *   - Find-or-create (creates new conversation)
 *   - Target must be at the same location
 *   - Fetching messages in a conversation
 *   - Sending messages in a conversation
 *   - Non-participant cannot access a conversation
 *   - Unread count endpoint
 *   - last_read_at updates when fetching messages
 *   - Staff can list location users for the DM recipient picker
 *   - Cannot create a conversation with yourself
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + two staff users
    // ──────────────────────────────────────────────

    private function seedLocationAndUsers(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $userA = User::create([
            'name' => 'User A',
            'email' => 'usera@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        $userB = User::create([
            'name' => 'User B',
            'email' => 'userb@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $location->id,
        ]);

        return compact('location', 'userA', 'userB');
    }

    // ══════════════════════════════════════════════
    //  LIST CONVERSATIONS
    // ══════════════════════════════════════════════

    public function test_user_can_list_conversations(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => now()],
            $seed['userB']->id => ['last_read_at' => now()],
        ]);

        // Reload to avoid cached state
        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->getJson('/api/conversations');

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    // ══════════════════════════════════════════════
    //  FIND-OR-CREATE: RETURNS EXISTING
    // ══════════════════════════════════════════════

    public function test_find_or_create_returns_existing_conversation(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => null],
            $seed['userB']->id => ['last_read_at' => null],
        ]);

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson('/api/conversations', [
                'user_id' => $seed['userB']->id,
            ]);

        $response->assertOk();
        $this->assertEquals($conversation->id, $response->json('id'));
    }

    // ══════════════════════════════════════════════
    //  FIND-OR-CREATE: CREATES NEW
    // ══════════════════════════════════════════════

    public function test_find_or_create_creates_new_conversation(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson('/api/conversations', [
                'user_id' => $seed['userB']->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('conversations', ['id' => $response->json('id')]);
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $response->json('id'),
            'user_id' => $seed['userA']->id,
        ]);
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $response->json('id'),
            'user_id' => $seed['userB']->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  TARGET MUST BE SAME LOCATION
    // ══════════════════════════════════════════════

    public function test_cannot_create_conversation_with_different_location_user(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $locationB = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationB->id,
        ]);

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson('/api/conversations', [
                'user_id' => $otherUser->id,
            ]);

        $response->assertStatus(422);
    }

    // ══════════════════════════════════════════════
    //  FETCH MESSAGES
    // ══════════════════════════════════════════════

    public function test_participant_can_fetch_messages(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => null],
            $seed['userB']->id => ['last_read_at' => null],
        ]);

        DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $seed['userA']->id,
            'body' => 'Hello!',
        ]);

        $response = $this->actingAs($seed['userB'], 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Hello!', $response->json()[0]['body']);
    }

    // ══════════════════════════════════════════════
    //  SEND MESSAGE
    // ══════════════════════════════════════════════

    public function test_participant_can_send_message(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => null],
            $seed['userB']->id => ['last_read_at' => null],
        ]);

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'Hey there!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'Hey there!')
            ->assertJsonPath('sender_id', $seed['userA']->id);
    }

    // ══════════════════════════════════════════════
    //  NON-PARTICIPANT CANNOT ACCESS
    // ══════════════════════════════════════════════

    public function test_non_participant_cannot_access_conversation(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => null],
            $seed['userB']->id => ['last_read_at' => null],
        ]);

        $response = $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  UNREAD COUNT
    // ══════════════════════════════════════════════

    public function test_unread_count_returns_correct_count(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $lastRead = now()->subHour();
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => $lastRead],
            $seed['userB']->id => ['last_read_at' => $lastRead],
        ]);

        // userB sends a message after userA's last_read_at
        DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $seed['userB']->id,
            'body' => 'New message',
        ]);

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->getJson('/api/conversations/unread-count');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    // ══════════════════════════════════════════════
    //  LAST_READ_AT UPDATES ON FETCH
    // ══════════════════════════════════════════════

    // ══════════════════════════════════════════════
    //  STAFF CAN LIST USERS FOR DM RECIPIENT PICKER
    // ══════════════════════════════════════════════

    /**
     * Staff (not just managers) can call GET /api/users to populate the DM
     * recipient picker. The response must only include users from the same
     * location — cross-location users must never appear.
     */
    public function test_staff_can_list_location_users_for_dm_picker(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a user at a different location — should NOT appear
        $otherLocation = Location::create([
            'name' => 'Other Bar',
            'address' => '999 Far Ave',
            'timezone' => 'America/Chicago',
        ]);
        User::create([
            'name' => 'Other Bar Staff',
            'email' => 'otherbar@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $otherLocation->id,
        ]);

        // Act: staff user (server role) fetches the user list
        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->getJson('/api/users');

        $response->assertOk();

        $names = array_column($response->json(), 'name');

        // Assert: both location users appear (self + userB)
        $this->assertContains('User A', $names);
        $this->assertContains('User B', $names);

        // Assert: cross-location user is excluded
        $this->assertNotContains('Other Bar Staff', $names);
        $this->assertCount(2, $response->json());
    }

    // ══════════════════════════════════════════════
    //  CANNOT START CONVERSATION WITH SELF
    // ══════════════════════════════════════════════

    /**
     * Attempting to create a conversation with yourself should return 422.
     * The frontend user picker excludes self, but the backend must also enforce this.
     */
    public function test_cannot_create_conversation_with_self(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['userA'], 'sanctum')
            ->postJson('/api/conversations', [
                'user_id' => $seed['userA']->id,
            ]);

        $response->assertStatus(422);
    }

    // ══════════════════════════════════════════════
    //  LAST_READ_AT UPDATES ON FETCH
    // ══════════════════════════════════════════════

    public function test_last_read_at_updates_when_fetching_messages(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $conversation = Conversation::create(['location_id' => $seed['location']->id]);
        $conversation->participants()->attach([
            $seed['userA']->id => ['last_read_at' => null],
            $seed['userB']->id => ['last_read_at' => null],
        ]);

        $this->actingAs($seed['userA'], 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages");

        $pivot = $conversation->participants()->where('user_id', $seed['userA']->id)->first()->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }
}
