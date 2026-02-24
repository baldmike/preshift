<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitialSetupRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * ConfigController handles system-wide configuration:
 * - Reading/updating settings (establishment name)
 * - Initial setup (wipe demo data and create real superadmin + location)
 * - Full database reset (superadmin only)
 */
class ConfigController extends Controller
{
    /**
     * GET /api/config/settings — Returns all settings as a key-value object.
     */
    public function getSettings(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    /**
     * PUT /api/config/settings — Updates establishment_name (superadmin only).
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Setting::set('establishment_name', $validated['establishment_name']);

        if (array_key_exists('time_off_advance_days', $validated)) {
            Setting::set('time_off_advance_days', (string) $validated['time_off_advance_days']);
        }

        return response()->json(['message' => 'Settings updated.']);
    }

    /**
     * POST /api/config/initial-setup — Wipe demo data and create a real superadmin account.
     *
     * Truncates all tables except `settings` and `migrations`, then creates a
     * new Location and a superadmin User with the provided details. Sets the
     * `establishment_name` setting to match the location name.
     */
    public function initialSetup(InitialSetupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Schema::disableForeignKeyConstraints();

        $tables = collect(Schema::getTableListing())
            ->filter(fn ($table) => !in_array($table, ['settings', 'migrations']));

        foreach ($tables as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $location = Location::create([
            'name' => $validated['location_name'],
            'address' => 'Update in location settings',
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'timezone' => 'America/New_York',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $location->id,
            'is_superadmin' => true,
        ]);

        Setting::set('establishment_name', $validated['location_name']);
        Setting::set('setup_complete', 'true');

        return response()->json([
            'message' => 'Setup complete. Your account has been created with password "password". Please log in.',
        ]);
    }

    /**
     * POST /api/config/reset — Full database reset (superadmin only).
     */
    public function fullReset(Request $request): JsonResponse
    {
        $callingUser = $request->user();

        Schema::disableForeignKeyConstraints();

        $tables = collect(Schema::getTableListing())
            ->filter(fn ($table) => !in_array($table, ['settings', 'migrations']));

        foreach ($tables as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $user = User::create([
            'name' => $callingUser->name,
            'email' => $callingUser->email,
            'password' => Hash::make('password'),
            'role' => $callingUser->role,
            'location_id' => null,
            'is_superadmin' => true,
        ]);

        return response()->json(['message' => 'Database has been reset. You have been re-created as superadmin. Please log in again.']);
    }
}
