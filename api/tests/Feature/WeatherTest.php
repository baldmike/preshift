<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WeatherTest
 *
 * Tests the /api/weather endpoint, which returns current conditions and daily
 * forecast data for a user's location via the Open-Meteo API. Verifies that:
 * - A 404 is returned when the location has no coordinates configured
 * - Weather data is returned and correctly formatted when coordinates exist
 * - Responses are cached to avoid redundant external API calls
 * - Authentication is required to access the endpoint
 * - Weather data is scoped to the authenticated user's location
 * - Both manager and staff roles can access weather data
 */
class WeatherTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + staff user
    // ──────────────────────────────────────────────

    private function seedLocationAndUsers(array $locationOverrides = []): array
    {
        $location = Location::create(array_merge([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ], $locationOverrides));

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

    /**
     * Fake Open-Meteo API response payload.
     */
    private function fakeWeatherResponse(): array
    {
        return [
            'current' => [
                'temperature_2m' => 72.3,
                'apparent_temperature' => 75.1,
                'relative_humidity_2m' => 45,
                'wind_speed_10m' => 8.2,
                'weather_code' => 1,
            ],
            'daily' => [
                'weather_code' => [2],
                'temperature_2m_max' => [78.4],
                'temperature_2m_min' => [62.1],
            ],
        ];
    }

    // ══════════════════════════════════════════════
    //  RETURNS 404 WHEN COORDINATES NOT SET
    // ══════════════════════════════════════════════

    /** Verifies that a 404 with an appropriate message is returned when the user's location has no latitude/longitude configured. */
    public function test_returns_404_when_location_has_no_coordinates(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/weather');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Location coordinates not configured.');
    }

    // ══════════════════════════════════════════════
    //  RETURNS WEATHER DATA WHEN COORDINATES SET
    // ══════════════════════════════════════════════

    /** Verifies that the endpoint returns correctly structured current conditions and daily forecast data when the location has coordinates configured. */
    public function test_returns_weather_data_when_coordinates_are_set(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeWeatherResponse(), 200),
        ]);

        $seed = $this->seedLocationAndUsers([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/weather');

        $response->assertOk()
            ->assertJsonPath('current.temperature', 72)
            ->assertJsonPath('current.feels_like', 75)
            ->assertJsonPath('current.humidity', 45)
            ->assertJsonPath('current.wind_speed', 8)
            ->assertJsonPath('current.weather_code', 1)
            ->assertJsonPath('current.description', 'Mainly Clear')
            ->assertJsonPath('today.high', 78)
            ->assertJsonPath('today.low', 62)
            ->assertJsonPath('today.weather_code', 2)
            ->assertJsonPath('today.description', 'Partly Cloudy');
    }

    // ══════════════════════════════════════════════
    //  RESPONSE IS CACHED
    // ══════════════════════════════════════════════

    /** Verifies that the weather response is cached so that consecutive requests only trigger a single external API call. */
    public function test_response_is_cached_for_30_minutes(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeWeatherResponse(), 200),
        ]);

        $seed = $this->seedLocationAndUsers([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // First request — should hit the external API
        $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/weather')
            ->assertOk();

        // Second request — should use cache (no second HTTP call)
        $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/weather')
            ->assertOk();

        // Open-Meteo should have been called only once
        Http::assertSentCount(1);
    }

    // ══════════════════════════════════════════════
    //  REQUIRES AUTHENTICATION
    // ══════════════════════════════════════════════

    /** Verifies that unauthenticated requests to the weather endpoint receive a 401 response. */
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/weather');

        $response->assertStatus(401);
    }

    // ══════════════════════════════════════════════
    //  DATA IS SCOPED TO USER'S LOCATION
    // ══════════════════════════════════════════════

    /** Verifies that weather data is scoped to the authenticated user's own location and does not leak data across locations. */
    public function test_weather_is_scoped_to_users_location(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeWeatherResponse(), 200),
        ]);

        // Location A has coordinates
        $seedA = $this->seedLocationAndUsers([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Location B has no coordinates
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

        // User A gets weather data
        $this->actingAs($seedA['staff'], 'sanctum')
            ->getJson('/api/weather')
            ->assertOk();

        // User B gets 404 (their location has no coordinates)
        $this->actingAs($userB, 'sanctum')
            ->getJson('/api/weather')
            ->assertStatus(404);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN ACCESS WEATHER TOO
    // ══════════════════════════════════════════════

    /** Verifies that managers can access the weather endpoint and receive valid weather data for their location. */
    public function test_manager_can_access_weather(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeWeatherResponse(), 200),
        ]);

        $seed = $this->seedLocationAndUsers([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/weather');

        $response->assertOk()
            ->assertJsonPath('current.temperature', 72);
    }
}
