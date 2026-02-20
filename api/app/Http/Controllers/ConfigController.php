<?php

namespace App\Http\Controllers;

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
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'establishment_name' => 'required|string|max:100',
        ]);

        Setting::set('establishment_name', $validated['establishment_name']);

        return response()->json(['message' => 'Settings updated.']);
    }

    /**
     * POST /api/config/reset — Full database reset (superadmin only).
     *
     * Truncates all tables except `settings` and `migrations`, then re-creates
     * the calling user as the sole superadmin with password "password".
     */
    public function fullReset(Request $request): JsonResponse
    {
        $callingUser = $request->user();

        Schema::disableForeignKeyConstraints();

        // Get all table names and exclude protected tables
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->filter(fn ($table) => !in_array($table, ['settings', 'migrations']));

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        // Re-create the calling user as the sole superadmin
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
