<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Notifications\ShiftDropRequestedNotification;
use App\Notifications\ShiftDropVolunteeredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Feature tests for role-filtered shift drop notifications and multi-role support.
 *
 * Covers the full lifecycle of the shift-drop workflow as it relates to roles:
 *   - Staff only see drops whose role matches their effective roles
 *   - Multi-role users can view and volunteer for either role's drops
 *   - Notifications are sent to managers/admins AND eligible same-role staff
 *   - Notification payloads include the shift role in the body text
 *   - Managers can assign multi-role via the user update endpoint
 *
 * Each test creates its own data via seedBase() and createEntry() helpers.
 * RefreshDatabase ensures a clean slate between tests.
 */
class ShiftDropRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the shared base data: location, manager, shift template, and
     * a published schedule. Returns an associative array keyed by entity name.
     *
     * @return array{location: Location, manager: User, template: ShiftTemplate, schedule: Schedule}
     */
    private function seedBase(): array
    {
        $location = Location::create([
            'name' => 'Role Test Location',
            'address' => '100 Test St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $manager->id,
        ]);

        return compact('location', 'manager', 'template', 'schedule');
    }

    /**
     * Create a schedule entry for the given user and role, using the base
     * schedule and template. The entry date is 3 days from now.
     *
     * @param  array  $base  The seedBase() return array.
     * @param  User   $user  The staff member to assign.
     * @param  string $role  "server" or "bartender".
     * @return ScheduleEntry
     */
    private function createEntry(array $base, User $user, string $role): ScheduleEntry
    {
        return ScheduleEntry::create([
            'schedule_id' => $base['schedule']->id,
            'user_id' => $user->id,
            'shift_template_id' => $base['template']->id,
            'date' => now()->addDays(3)->toDateString(),
            'role' => $role,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  1. Staff sees only matching role drops on drop board
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a bartender cannot see open server shift drops on the
     * drop board index. The index endpoint filters by the authenticated
     * staff member's effective roles.
     */
    public function test_staff_sees_only_matching_role_drops(): void
    {
        Event::fake();
        $base = $this->seedBase();

        $server = User::create([
            'name' => 'Server A',
            'email' => 'server-a@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $bartender = User::create([
            'name' => 'Bartender A',
            'email' => 'bartender-a@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $base['location']->id,
        ]);

        // Create a server shift drop — bartender should not see it.
        $serverEntry = $this->createEntry($base, $server, 'server');
        ShiftDrop::create([
            'schedule_entry_id' => $serverEntry->id,
            'requested_by' => $server->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($bartender, 'sanctum')
            ->getJson('/api/shift-drops');

        $response->assertOk();
        $this->assertCount(0, $response->json());
    }

    // ══════════════════════════════════════════════════════════════════════
    //  2. Multi-role user sees drops for all their roles
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a user with roles ["server","bartender"] sees open drops
     * for BOTH server and bartender shifts on the drop board index.
     */
    public function test_multi_role_user_sees_drops_for_all_roles(): void
    {
        Event::fake();
        $base = $this->seedBase();

        $serverOnly = User::create([
            'name' => 'Server Only',
            'email' => 'server-only@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $bartenderOnly = User::create([
            'name' => 'Bartender Only',
            'email' => 'bartender-only@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $base['location']->id,
        ]);

        $multiRole = User::create([
            'name' => 'Multi Role',
            'email' => 'multi@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'roles' => ['server', 'bartender'],
            'location_id' => $base['location']->id,
        ]);

        // Create one server drop and one bartender drop
        $serverEntry = $this->createEntry($base, $serverOnly, 'server');
        ShiftDrop::create([
            'schedule_entry_id' => $serverEntry->id,
            'requested_by' => $serverOnly->id,
            'status' => 'open',
        ]);

        $bartenderEntry = ScheduleEntry::create([
            'schedule_id' => $base['schedule']->id,
            'user_id' => $bartenderOnly->id,
            'shift_template_id' => $base['template']->id,
            'date' => now()->addDays(4)->toDateString(),
            'role' => 'bartender',
        ]);
        ShiftDrop::create([
            'schedule_entry_id' => $bartenderEntry->id,
            'requested_by' => $bartenderOnly->id,
            'status' => 'open',
        ]);

        // Multi-role user should see both drops
        $response = $this->actingAs($multiRole, 'sanctum')
            ->getJson('/api/shift-drops');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    // ══════════════════════════════════════════════════════════════════════
    //  3. Multi-role user can volunteer for either role
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a user whose primary role is "server" but has
     * roles=["server","bartender"] can successfully volunteer for a
     * bartender shift drop. Previously this was blocked because the
     * volunteer endpoint compared only the primary role.
     */
    public function test_multi_role_user_can_volunteer_for_either_role(): void
    {
        Event::fake();
        Notification::fake();
        $base = $this->seedBase();

        $bartenderOnly = User::create([
            'name' => 'Bartender Only',
            'email' => 'bartender-only@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $base['location']->id,
        ]);

        $multiRole = User::create([
            'name' => 'Multi Role',
            'email' => 'multi@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'roles' => ['server', 'bartender'],
            'location_id' => $base['location']->id,
        ]);

        // Bartender shift drop
        $entry = $this->createEntry($base, $bartenderOnly, 'bartender');
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by' => $bartenderOnly->id,
            'status' => 'open',
        ]);

        // Multi-role user (primary=server, also bartender) volunteers for bartender drop
        $response = $this->actingAs($multiRole, 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer");

        $response->assertOk();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  4. Single-role user cannot volunteer for wrong role
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a server-only user (no multi-role) receives a 422 when
     * trying to volunteer for a bartender shift drop. This is the existing
     * guard behaviour preserved after the multi-role refactor.
     */
    public function test_single_role_user_cannot_volunteer_for_wrong_role(): void
    {
        Event::fake();
        Notification::fake();
        $base = $this->seedBase();

        $bartenderOnly = User::create([
            'name' => 'Bartender Only',
            'email' => 'bartender-only@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $base['location']->id,
        ]);

        $serverOnly = User::create([
            'name' => 'Server Only',
            'email' => 'server-only@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        // Bartender shift drop
        $entry = $this->createEntry($base, $bartenderOnly, 'bartender');
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by' => $bartenderOnly->id,
            'status' => 'open',
        ]);

        // Server (no bartender role) tries to volunteer — should be rejected
        $response = $this->actingAs($serverOnly, 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You must have the same role to pick up this shift.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  5. Drop notification sent to eligible same-role staff
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that when a server drops a shift, the ShiftDropRequestedNotification
     * is sent to both the manager AND another server at the same location.
     * This confirms the new recipient expansion beyond just managers/admins.
     */
    public function test_drop_notification_sent_to_eligible_same_role_staff(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server1 = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $server2 = User::create([
            'name' => 'Server Two',
            'email' => 'server2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server1, 'server');

        $this->actingAs($server1, 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $entry->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        // server2 should receive the notification
        Notification::assertSentTo($server2, ShiftDropRequestedNotification::class);
        // manager should also receive it
        Notification::assertSentTo($base['manager'], ShiftDropRequestedNotification::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  6. Drop notification NOT sent to wrong-role staff
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that when a server drops a shift, a bartender at the same
     * location does NOT receive the notification. The recipient query
     * filters staff by role match.
     */
    public function test_drop_notification_not_sent_to_wrong_role_staff(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server1 = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $bartender = User::create([
            'name' => 'Bartender One',
            'email' => 'bartender1@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server1, 'server');

        $this->actingAs($server1, 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $entry->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        // Bartender should NOT receive the notification for a server shift drop
        Notification::assertNotSentTo($bartender, ShiftDropRequestedNotification::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  7. Drop notification body includes role
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that the ShiftDropRequestedNotification payload includes
     * "server shift" in the body text and a discrete `role` key set to
     * the schedule entry's role value.
     */
    public function test_drop_notification_body_includes_role(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server, 'server');

        $this->actingAs($server, 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $entry->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($base['manager'], ShiftDropRequestedNotification::class, function ($notification) {
            $data = $notification->toArray($notification);
            return str_contains($data['body'], 'server shift') && $data['role'] === 'server';
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  8. Manager can set multi-role via user update
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a manager can PATCH a user to set the `roles` JSON array,
     * enabling multi-role support. After the update, the user's hasRole()
     * should return true for both the primary and secondary role.
     */
    public function test_manager_can_set_multi_role_via_user_update(): void
    {
        $base = $this->seedBase();

        $server = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $response = $this->actingAs($base['manager'], 'sanctum')
            ->patchJson("/api/users/{$server->id}", [
                'name' => $server->name,
                'email' => $server->email,
                'role' => 'server',
                'roles' => ['server', 'bartender'],
            ]);

        $response->assertOk();
        $server->refresh();
        $this->assertEquals(['server', 'bartender'], $server->roles);
        $this->assertTrue($server->hasRole('bartender'));
        $this->assertTrue($server->hasRole('server'));
    }

    // ══════════════════════════════════════════════════════════════════════
    //  9. Volunteer notification body includes role
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that the ShiftDropVolunteeredNotification payload includes
     * the shift role in the body text ("server shift") and a discrete
     * `role` key, matching the behaviour of the drop-requested notification.
     */
    public function test_volunteer_notification_body_includes_role(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server1 = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $server2 = User::create([
            'name' => 'Server Two',
            'email' => 'server2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server1, 'server');
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by' => $server1->id,
            'status' => 'open',
        ]);

        // server2 volunteers
        $this->actingAs($server2, 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer")
            ->assertOk();

        Notification::assertSentTo($base['manager'], ShiftDropVolunteeredNotification::class, function ($notification) {
            $data = $notification->toArray($notification);
            return str_contains($data['body'], 'server shift') && $data['role'] === 'server';
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  10. Multi-role staff receives notification via roles JSON
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that a multi-role user whose `roles` JSON contains "server"
     * receives the drop notification when a server shift is dropped, even
     * though their primary role is "bartender". This tests the
     * whereJsonContains branch of the recipient query.
     */
    public function test_multi_role_staff_receives_notification_via_roles_json(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server1 = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        // Primary bartender who also works server shifts
        $multiRole = User::create([
            'name' => 'Multi Role',
            'email' => 'multi@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'roles' => ['bartender', 'server'],
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server1, 'server');

        $this->actingAs($server1, 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $entry->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        // Multi-role user should receive the notification even though their
        // primary role is bartender — their `roles` JSON contains "server".
        Notification::assertSentTo($multiRole, ShiftDropRequestedNotification::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  11. Requester is excluded from drop notification
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verify that the user who drops the shift does NOT receive their own
     * notification, even though they are an eligible same-role staff member.
     * The recipient query explicitly excludes the requester's ID.
     */
    public function test_requester_excluded_from_drop_notification(): void
    {
        Notification::fake();
        Event::fake();
        $base = $this->seedBase();

        $server = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $base['location']->id,
        ]);

        $entry = $this->createEntry($base, $server, 'server');

        $this->actingAs($server, 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $entry->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        // The requester should NOT receive their own notification
        Notification::assertNotSentTo($server, ShiftDropRequestedNotification::class);
    }
}
