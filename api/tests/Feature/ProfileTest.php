<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function seedLocationAndUsers(): array
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

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/profile', ['name' => 'New Name']);

        $response->assertStatus(401);
    }

    public function test_update_name(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['name' => 'Updated Name']);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');

        $seed['staff']->refresh();
        $this->assertEquals('Updated Name', $seed['staff']->name);
    }

    public function test_update_availability(): void
    {
        $seed = $this->seedLocationAndUsers();

        $availability = [
            'monday' => ['10:30', '16:30'],
            'tuesday' => ['open'],
        ];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['availability' => $availability]);

        $response->assertOk()
            ->assertJsonPath('availability.monday', ['10:30', '16:30'])
            ->assertJsonPath('availability.tuesday', ['open']);
    }

    public function test_ignores_role_email_and_location_id(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', [
                'name' => 'New Name',
                'role' => 'admin',
                'email' => 'hacker@evil.com',
                'location_id' => 999,
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('role', 'server')
            ->assertJsonPath('email', 'server@test.com');

        $seed['staff']->refresh();
        $this->assertEquals('server', $seed['staff']->role);
        $this->assertEquals('server@test.com', $seed['staff']->email);
        $this->assertEquals($seed['location']->id, $seed['staff']->location_id);
    }

    public function test_returns_location_relationship(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['name' => 'Test']);

        $response->assertOk()
            ->assertJsonPath('location.name', 'Test Location');
    }
}
