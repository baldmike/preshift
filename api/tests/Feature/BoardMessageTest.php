<?php

namespace Tests\Feature;

use App\Models\BoardMessage;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for the BoardMessage API endpoints.
 *
 * Verifies:
 *   - Listing top-level posts with visibility filtering
 *   - Creating posts and replies
 *   - Editing posts (author and manager)
 *   - Deleting posts
 *   - Pinning/unpinning (admin/manager only)
 *   - Staff cannot see managers-only posts
 *   - Staff cannot edit other users' posts
 *   - Cross-location isolation
 */
class BoardMessageTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + manager + staff user
    // ──────────────────────────────────────────────

    private function seedLocationAndUsers(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $staff = User::create([
            'name' => 'Server User',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        return compact('location', 'manager', 'staff');
    }

    // ══════════════════════════════════════════════
    //  LIST BOARD MESSAGES
    // ══════════════════════════════════════════════

    public function test_staff_can_list_board_messages(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Hello team!',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/board-messages');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Hello team!', $response->json()[0]['body']);
    }

    // ══════════════════════════════════════════════
    //  CREATE POST
    // ══════════════════════════════════════════════

    public function test_staff_can_create_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/board-messages', [
                'body' => 'My first post',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'My first post')
            ->assertJsonPath('user_id', $seed['staff']->id);
    }

    // ══════════════════════════════════════════════
    //  CREATE REPLY
    // ══════════════════════════════════════════════

    public function test_staff_can_create_reply(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Original post',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/board-messages', [
                'body' => 'A reply',
                'parent_id' => $post->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('parent_id', $post->id);
    }

    // ══════════════════════════════════════════════
    //  EDIT OWN POST
    // ══════════════════════════════════════════════

    public function test_author_can_edit_own_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['staff']->id,
            'body' => 'Original text',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->patchJson("/api/board-messages/{$post->id}", [
                'body' => 'Updated text',
            ]);

        $response->assertOk()
            ->assertJsonPath('body', 'Updated text');
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN EDIT ANY POST
    // ══════════════════════════════════════════════

    public function test_manager_can_edit_any_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['staff']->id,
            'body' => 'Staff post',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/board-messages/{$post->id}", [
                'body' => 'Manager edited',
            ]);

        $response->assertOk()
            ->assertJsonPath('body', 'Manager edited');
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT EDIT OTHERS' POSTS
    // ══════════════════════════════════════════════

    public function test_staff_cannot_edit_other_users_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $otherStaff = User::create([
            'name' => 'Other Server',
            'email' => 'other@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $otherStaff->id,
            'body' => 'Other user post',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->patchJson("/api/board-messages/{$post->id}", [
                'body' => 'Unauthorized edit',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  DELETE POST
    // ══════════════════════════════════════════════

    public function test_author_can_delete_own_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['staff']->id,
            'body' => 'To be deleted',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->deleteJson("/api/board-messages/{$post->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('board_messages', ['id' => $post->id]);
    }

    // ══════════════════════════════════════════════
    //  PIN / UNPIN (ADMIN/MANAGER ONLY)
    // ══════════════════════════════════════════════

    public function test_manager_can_pin_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['staff']->id,
            'body' => 'Pin me',
            'pinned' => false,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/board-messages/{$post->id}/pin");

        $response->assertOk()
            ->assertJsonPath('pinned', true);
    }

    public function test_staff_cannot_pin_post(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $post = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['staff']->id,
            'body' => 'Cannot pin',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson("/api/board-messages/{$post->id}/pin");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  VISIBILITY: STAFF CANNOT SEE MANAGERS-ONLY
    // ══════════════════════════════════════════════

    public function test_staff_cannot_see_managers_only_posts(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Managers only post',
            'visibility' => 'managers',
        ]);

        BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Public post',
            'visibility' => 'all',
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/board-messages');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Public post', $response->json()[0]['body']);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION ISOLATION
    // ══════════════════════════════════════════════

    public function test_user_cannot_see_other_locations_posts(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $locationB = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);

        $userB = User::create([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationB->id,
        ]);

        BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Location A post',
        ]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/board-messages');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }
}
