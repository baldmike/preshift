<?php

namespace Tests\Feature;

use App\Models\BoardMessage;
use App\Models\Location;
use App\Models\ManagerLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * FormRequestValidationTest — Tests that every Form Request rejects invalid
 * payloads with 422 + the correct validation error keys.
 *
 * Covers:
 *   - StoreEightySixedRequest (missing item_name)
 *   - StoreAnnouncementRequest (missing title/body/priority, invalid priority)
 *   - StoreSpecialRequest (missing title/type/starts_at, invalid type)
 *   - StorePushItemRequest (missing title/priority, invalid priority)
 *   - StoreEventRequest (missing title/event_date)
 *   - StoreMenuItemRequest (missing name/type, invalid type)
 *   - StoreCategoryRequest (missing name)
 *   - StoreShiftTemplateRequest (missing name/start_time, invalid time format)
 *   - StoreScheduleRequest (missing week_start)
 *   - StoreUserRequest (missing name/email/password/role, invalid role)
 *   - UpdateUserRequest (missing name/email/role)
 *   - StoreLocationRequest (missing name, admin only)
 *   - StoreBoardMessageRequest (missing body, body max:2000)
 *   - UpdateBoardMessageRequest (missing body)
 *   - StoreConversationRequest (missing user_id, nonexistent user_id)
 *   - UploadProfilePhotoRequest (missing photo)
 *   - UpdateProfileRequest (name max:255)
 *   - StoreManagerLogRequest (missing log_date/body)
 */
class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a location and manager for tests that need a manager-level user.
     */
    private function seedManager(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '100 Test St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        return compact('location', 'manager');
    }

    /**
     * Create a location and admin for tests that need an admin-level user.
     */
    private function seedAdmin(): array
    {
        $location = Location::create([
            'name' => 'Admin Location',
            'address' => '200 Admin St',
            'timezone' => 'America/New_York',
        ]);

        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $location->id,
        ]);

        return compact('location', 'admin');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EIGHTY-SIXED VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_eighty_sixed_requires_item_name(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/eighty-sixed', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['item_name']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ANNOUNCEMENT VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_announcement_requires_title_body_priority(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/announcements', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'body', 'priority']);
    }

    public function test_store_announcement_rejects_invalid_priority(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/announcements', [
                'title' => 'Test',
                'body' => 'Body text',
                'priority' => 'extreme',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SPECIAL VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_special_requires_title_type_starts_at(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/specials', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'type', 'starts_at']);
    }

    public function test_store_special_rejects_invalid_type(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/specials', [
                'title' => 'Wings',
                'type' => 'yearly',
                'starts_at' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PUSH ITEM VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_push_item_requires_title_priority(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/push-items', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'priority']);
    }

    public function test_store_push_item_rejects_invalid_priority(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/push-items', [
                'title' => 'Wine',
                'priority' => 'critical',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EVENT VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_event_requires_title_event_date(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/events', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'event_date']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MENU ITEM VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_menu_item_requires_name_type(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/menu-items', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_store_menu_item_rejects_invalid_type(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/menu-items', [
                'name' => 'Burger',
                'type' => 'snack',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATEGORY VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_category_requires_name(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/categories', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SHIFT TEMPLATE VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_shift_template_requires_name_start_time(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/shift-templates', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'start_time']);
    }

    public function test_store_shift_template_rejects_invalid_time_format(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/shift-templates', [
                'name' => 'Lunch',
                'start_time' => '10:30:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_schedule_requires_week_start(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedules', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['week_start']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  USER VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_user_requires_name_email_password_role(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/users', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_store_user_rejects_invalid_role(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Test User',
                'email' => 'newuser@test.com',
                'password' => 'password123',
                'role' => 'cook',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    }

    public function test_update_user_requires_name_email_role(): void
    {
        $seed = $this->seedManager();

        $target = User::create([
            'name' => 'Target User',
            'email' => 'target@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/users/{$target->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'role']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  LOCATION VALIDATION (admin only)
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_location_requires_name(): void
    {
        $seed = $this->seedAdmin();

        $response = $this->actingAs($seed['admin'], 'sanctum')
            ->postJson('/api/locations', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BOARD MESSAGE VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_board_message_requires_body(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/board-messages', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_store_board_message_rejects_body_over_max(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/board-messages', [
                'body' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_update_board_message_requires_body(): void
    {
        $seed = $this->seedManager();

        $message = BoardMessage::create([
            'location_id' => $seed['location']->id,
            'user_id' => $seed['manager']->id,
            'body' => 'Original message',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/board-messages/{$message->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CONVERSATION VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_conversation_requires_user_id(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/conversations', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_store_conversation_rejects_nonexistent_user_id(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/conversations', [
                'user_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PROFILE PHOTO VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_upload_profile_photo_requires_photo(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/profile/photo', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PROFILE UPDATE VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_update_profile_rejects_name_over_max(): void
    {
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->putJson('/api/profile', [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MANAGER LOG VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_store_manager_log_requires_log_date_body(): void
    {
        Http::fake();
        $seed = $this->seedManager();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/manager-logs', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['log_date', 'body']);
    }
}
