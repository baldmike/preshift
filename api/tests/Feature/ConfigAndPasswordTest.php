<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for:
 * - SuperAdmin middleware (CheckSuperAdmin)
 * - Config settings endpoints (GET/PUT /api/config/settings)
 * - Change password endpoint (POST /api/change-password)
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
