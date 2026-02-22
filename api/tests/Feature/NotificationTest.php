<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\ShiftTemplate;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\ShiftDropRequestedNotification;
use App\Notifications\ShiftDropVolunteeredNotification;
use App\Notifications\TimeOffRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the base data: location, manager, admin, and a server staff member.
     */
    private function seedLocationAndUsers(): array
    {
        $location = Location::create([
            'name' => 'Notification Test Location',
            'address' => '789 Elm St',
            'timezone' => 'America/New_York',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $location->id,
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

        return compact('location', 'admin', 'manager', 'staff');
    }

    /**
     * Create a full shift drop scenario: template, published schedule, entry assigned to staff.
     */
    private function seedShiftDropScenario(array $seed): array
    {
        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        $futureDate = now()->addDays(3)->toDateString();
        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $futureDate,
            'role' => 'server',
        ]);

        // Create a second server for volunteer tests
        $server2 = User::create([
            'name' => 'Server Two',
            'email' => 'server2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        return compact('template', 'schedule', 'entry', 'server2');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTIFICATION DISPATCH TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_shift_drop_store_sends_notification_to_managers(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();
        $drop = $this->seedShiftDropScenario($seed);

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $drop['entry']->id,
                'reason' => 'Sick',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($seed['manager'], ShiftDropRequestedNotification::class);
        Notification::assertSentTo($seed['admin'], ShiftDropRequestedNotification::class);
    }

    public function test_shift_drop_volunteer_sends_notification_to_managers(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();
        $drop = $this->seedShiftDropScenario($seed);

        // First create the shift drop
        $shiftDrop = ShiftDrop::create([
            'schedule_entry_id' => $drop['entry']->id,
            'requested_by' => $seed['staff']->id,
            'reason' => 'Sick',
            'status' => 'open',
        ]);

        // Now volunteer as server2
        $this->actingAs($drop['server2'], 'sanctum')
            ->postJson("/api/shift-drops/{$shiftDrop->id}/volunteer")
            ->assertOk();

        Notification::assertSentTo($seed['manager'], ShiftDropVolunteeredNotification::class);
        Notification::assertSentTo($seed['admin'], ShiftDropVolunteeredNotification::class);
    }

    public function test_time_off_store_sends_notification_to_managers(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Vacation',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($seed['manager'], TimeOffRequestedNotification::class);
        Notification::assertSentTo($seed['admin'], TimeOffRequestedNotification::class);
    }

    public function test_notifications_not_sent_to_staff(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Vacation',
            ])
            ->assertStatus(201);

        Notification::assertNotSentTo($seed['staff'], TimeOffRequestedNotification::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTIFICATION ENDPOINT TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_list_notifications(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a database notification directly
        $seed['manager']->notifications()->create([
            'id' => fake()->uuid(),
            'type' => 'App\\Notifications\\TimeOffRequestedNotification',
            'data' => [
                'type' => 'time_off_requested',
                'title' => 'Time Off Request',
                'body' => 'Server User requested time off.',
                'link' => '/manage/time-off',
                'source_id' => 1,
            ],
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_manager_can_mark_notification_read(): void
    {
        $seed = $this->seedLocationAndUsers();

        $notification = $seed['manager']->notifications()->create([
            'id' => fake()->uuid(),
            'type' => 'App\\Notifications\\TimeOffRequestedNotification',
            'data' => [
                'type' => 'time_off_requested',
                'title' => 'Time Off Request',
                'body' => 'Server User requested time off.',
                'link' => '/manage/time-off',
                'source_id' => 1,
            ],
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_manager_can_mark_all_notifications_read(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create two unread notifications
        for ($i = 0; $i < 2; $i++) {
            $seed['manager']->notifications()->create([
                'id' => fake()->uuid(),
                'type' => 'App\\Notifications\\TimeOffRequestedNotification',
                'data' => [
                    'type' => 'time_off_requested',
                    'title' => 'Time Off Request',
                    'body' => 'Request ' . ($i + 1),
                    'link' => '/manage/time-off',
                    'source_id' => $i + 1,
                ],
            ]);
        }

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, $seed['manager']->unreadNotifications()->count());
    }

    public function test_staff_cannot_access_notification_endpoints(): void
    {
        $seed = $this->seedLocationAndUsers();

        $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/notifications')
            ->assertStatus(403);

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/notifications/some-uuid/read')
            ->assertStatus(403);

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/notifications/read-all')
            ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EMAIL ALERT TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_email_sent_when_email_alerts_enabled(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();
        $seed['location']->update(['email_alerts_enabled' => true]);

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Vacation',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($seed['manager'], TimeOffRequestedNotification::class, function ($notification, $channels) {
            return in_array('mail', $channels);
        });
    }

    public function test_email_not_sent_when_email_alerts_disabled(): void
    {
        Notification::fake();
        Event::fake();

        $seed = $this->seedLocationAndUsers();
        // email_alerts_enabled defaults to false

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Vacation',
            ])
            ->assertStatus(201);

        Notification::assertSentTo($seed['manager'], TimeOffRequestedNotification::class, function ($notification, $channels) {
            return ! in_array('mail', $channels);
        });
    }
}
