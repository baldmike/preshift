<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\Setting;
use App\Models\ShiftTemplate;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for:
 * - SuperAdmin middleware (CheckSuperAdmin)
 * - Config settings endpoints (GET/PUT /api/config/settings)
 * - Change password endpoint (POST /api/change-password)
 * - Granular remove endpoints (items, schedules, employees)
 * - Full database reset endpoint
 */
class ConfigAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
            'is_superadmin' => true,
        ]);

        $regular = User::create([
            'name' => 'Regular User',
            'email' => 'regular@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
            'is_superadmin' => false,
        ]);

        return compact('location', 'superadmin', 'regular');
    }

    // ── SuperAdmin Middleware ──

    public function test_superadmin_middleware_blocks_non_superadmin(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->putJson('/api/config/settings', ['establishment_name' => 'Test']);

        $response->assertStatus(403);
    }

    public function test_superadmin_middleware_allows_superadmin(): void
    {
        ['superadmin' => $superadmin] = $this->seedUsers();

        $response = $this->actingAs($superadmin)
            ->putJson('/api/config/settings', ['establishment_name' => 'Test']);

        $response->assertStatus(200);
    }

    // ── GET /api/config/settings ──

    public function test_get_settings_returns_all_settings(): void
    {
        ['regular' => $regular] = $this->seedUsers();
        Setting::create(['key' => 'establishment_name', 'value' => 'The Anchor']);

        $response = $this->actingAs($regular)->getJson('/api/config/settings');

        $response->assertStatus(200)
            ->assertJson(['establishment_name' => 'The Anchor']);
    }

    // ── PUT /api/config/settings ──

    public function test_update_settings_saves_establishment_name(): void
    {
        ['superadmin' => $superadmin] = $this->seedUsers();

        $this->actingAs($superadmin)
            ->putJson('/api/config/settings', ['establishment_name' => 'New Name'])
            ->assertStatus(200);

        $this->assertEquals('New Name', Setting::get('establishment_name'));
    }

    public function test_update_settings_validates_establishment_name(): void
    {
        ['superadmin' => $superadmin] = $this->seedUsers();

        $this->actingAs($superadmin)
            ->putJson('/api/config/settings', ['establishment_name' => ''])
            ->assertStatus(422);
    }

    // ── POST /api/change-password ──

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->postJson('/api/change-password', [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully.']);

        // Verify the password was actually changed
        $regular->refresh();
        $this->assertTrue(Hash::check('newpassword123', $regular->password));
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->postJson('/api/change-password', [
                'current_password' => 'wrong_password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Current password is incorrect.']);
    }

    public function test_change_password_fails_when_confirmation_does_not_match(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->postJson('/api/change-password', [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'different',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_fails_when_too_short(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->postJson('/api/change-password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertStatus(422);
    }

    // ── POST /api/config/remove-items ──

    public function test_remove_items_blocked_for_non_superadmin(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $this->actingAs($regular)
            ->postJson('/api/config/remove-items')
            ->assertStatus(403);
    }

    public function test_remove_items_clears_all_operational_data(): void
    {
        ['superadmin' => $superadmin, 'location' => $location] = $this->seedUsers();

        $category = Category::create(['name' => 'Apps', 'location_id' => $location->id, 'sort_order' => 1]);
        MenuItem::create(['name' => 'Fries', 'location_id' => $location->id, 'category_id' => $category->id, 'type' => 'food']);
        EightySixed::create(['item_name' => 'Salmon', 'location_id' => $location->id, 'eighty_sixed_by' => $superadmin->id]);
        Announcement::create(['title' => 'Test', 'body' => 'msg', 'location_id' => $location->id, 'priority' => 'normal', 'posted_by' => $superadmin->id]);
        Special::create(['title' => 'HH', 'location_id' => $location->id, 'type' => 'daily', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'created_by' => $superadmin->id]);

        $this->actingAs($superadmin)
            ->postJson('/api/config/remove-items')
            ->assertStatus(200);

        $this->assertEquals(0, Category::count());
        $this->assertEquals(0, MenuItem::count());
        $this->assertEquals(0, EightySixed::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertEquals(0, Special::count());
    }

    // ── POST /api/config/remove-schedules ──

    public function test_remove_schedules_blocked_for_non_superadmin(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $this->actingAs($regular)
            ->postJson('/api/config/remove-schedules')
            ->assertStatus(403);
    }

    public function test_remove_schedules_clears_all_scheduling_data(): void
    {
        ['superadmin' => $superadmin, 'location' => $location] = $this->seedUsers();

        $template = ShiftTemplate::create(['name' => 'Lunch', 'location_id' => $location->id, 'start_time' => '10:30']);
        $schedule = Schedule::create(['location_id' => $location->id, 'week_start' => '2026-02-16', 'status' => 'draft']);
        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $superadmin->id,
            'shift_template_id' => $template->id,
            'date' => '2026-02-16',
            'role' => 'server',
        ]);

        $this->actingAs($superadmin)
            ->postJson('/api/config/remove-schedules')
            ->assertStatus(200);

        $this->assertEquals(0, ShiftTemplate::count());
        $this->assertEquals(0, Schedule::count());
        $this->assertEquals(0, ScheduleEntry::count());
    }

    // ── POST /api/config/remove-employees ──

    public function test_remove_employees_blocked_for_non_superadmin(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $this->actingAs($regular)
            ->postJson('/api/config/remove-employees')
            ->assertStatus(403);
    }

    public function test_remove_employees_keeps_only_superadmin(): void
    {
        ['superadmin' => $superadmin] = $this->seedUsers();

        $this->assertEquals(2, User::count());

        $this->actingAs($superadmin)
            ->postJson('/api/config/remove-employees')
            ->assertStatus(200);

        $this->assertEquals(1, User::count());
        $this->assertEquals($superadmin->id, User::first()->id);
    }

    // ── POST /api/config/reset ──

    public function test_full_reset_blocked_for_non_superadmin(): void
    {
        ['regular' => $regular] = $this->seedUsers();

        $response = $this->actingAs($regular)
            ->postJson('/api/config/reset');

        $response->assertStatus(403);
    }

    public function test_full_reset_truncates_data_and_recreates_superadmin(): void
    {
        ['superadmin' => $superadmin, 'regular' => $regular] = $this->seedUsers();

        // Verify we have 2 users before reset
        $this->assertEquals(2, User::count());

        $response = $this->actingAs($superadmin)
            ->postJson('/api/config/reset');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Database has been reset. You have been re-created as superadmin. Please log in again.']);

        // After reset: only 1 user remains — the re-created superadmin
        $this->assertEquals(1, User::count());

        $recreated = User::first();
        $this->assertEquals($superadmin->email, $recreated->email);
        $this->assertTrue($recreated->is_superadmin);
        $this->assertTrue(Hash::check('password', $recreated->password));
    }
}
