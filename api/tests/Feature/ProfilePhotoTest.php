<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for profile photo upload and deletion.
 *
 * Verifies:
 *   - Authenticated users can upload a profile photo (POST /api/profile/photo)
 *   - Uploading a replacement deletes the old file
 *   - Non-image files are rejected with 422
 *   - Oversized files (>5 MB) are rejected with 422
 *   - Authenticated users can delete their profile photo (DELETE /api/profile/photo)
 *   - Deleting when no photo exists returns success gracefully
 *   - Unauthenticated upload returns 401
 *   - Unauthenticated delete returns 401
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location with one server user for profile photo tests.
     *
     * @return array{location: Location, staff: User}
     */
    private function seedLocationAndUser(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $staff = User::create([
            'name' => 'Server User',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        return compact('location', 'staff');
    }

    /**
     * Verify that an authenticated user can upload a profile photo.
     * The file should be stored on the public disk and the user's
     * profile_photo_path should be set.
     */
    public function test_upload_profile_photo(): void
    {
        Storage::fake('public');
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ]);

        $response->assertOk()
            ->assertJsonPath('profile_photo_url', fn ($url) => str_contains($url, 'profile-photos/'));

        $seed['staff']->refresh();
        $this->assertNotNull($seed['staff']->profile_photo_path);
        Storage::disk('public')->assertExists($seed['staff']->profile_photo_path);
    }

    /**
     * Verify that uploading a new photo replaces the old one.
     * The previous file should be deleted from disk.
     */
    public function test_upload_replaces_old_photo(): void
    {
        Storage::fake('public');
        $seed = $this->seedLocationAndUser();

        // Upload first photo
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->image('first.jpg', 200, 200),
            ]);

        $seed['staff']->refresh();
        $oldPath = $seed['staff']->profile_photo_path;

        // Upload replacement photo
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->image('second.png', 200, 200),
            ]);

        $seed['staff']->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($seed['staff']->profile_photo_path);
    }

    /**
     * Verify that uploading a non-image file is rejected with 422.
     */
    public function test_rejects_non_image(): void
    {
        Storage::fake('public');
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    /**
     * Verify that uploading a file larger than 5 MB is rejected with 422.
     */
    public function test_rejects_oversized_file(): void
    {
        Storage::fake('public');
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->image('huge.jpg')->size(6000),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    /**
     * Verify that an authenticated user can delete their profile photo.
     * The file should be removed from disk and the column set to null.
     */
    public function test_delete_profile_photo(): void
    {
        Storage::fake('public');
        $seed = $this->seedLocationAndUser();

        // Upload a photo first
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/profile/photo', [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ]);

        $seed['staff']->refresh();
        $oldPath = $seed['staff']->profile_photo_path;

        // Delete it
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->deleteJson('/api/profile/photo');

        $response->assertOk()
            ->assertJsonPath('profile_photo_url', null);

        $seed['staff']->refresh();
        $this->assertNull($seed['staff']->profile_photo_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    /**
     * Verify that deleting when no photo exists returns success gracefully.
     */
    public function test_delete_when_no_photo_exists(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->deleteJson('/api/profile/photo');

        $response->assertOk()
            ->assertJsonPath('profile_photo_url', null);
    }

    /**
     * Verify that unauthenticated upload returns 401.
     */
    public function test_unauthenticated_upload(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/profile/photo', [
            'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertStatus(401);
    }

    /**
     * Verify that unauthenticated delete returns 401.
     */
    public function test_unauthenticated_delete(): void
    {
        $response = $this->deleteJson('/api/profile/photo');

        $response->assertStatus(401);
    }
}
