<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
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
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Setting::set('establishment_name', $validated['establishment_name']);

        return response()->json(['message' => 'Settings updated.']);
    }

    /**
     * POST /api/config/remove-items — Remove all operational items (superadmin only).
     */
    public function removeItems(): JsonResponse
    {
        $tables = ['acknowledgments', 'eighty_sixed', 'specials', 'push_items', 'announcements', 'menu_items', 'categories'];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        return response()->json(['message' => 'All items have been removed.']);
    }

    /**
     * POST /api/config/remove-schedules — Remove all scheduling data (superadmin only).
     */
    public function removeSchedules(): JsonResponse
    {
        $tables = ['shift_drop_volunteers', 'shift_drops', 'schedule_entries', 'schedules', 'shift_templates', 'time_off_requests'];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        return response()->json(['message' => 'All schedules have been removed.']);
    }

    /**
     * POST /api/config/remove-employees — Remove all employees except the calling superadmin.
     */
    public function removeEmployees(Request $request): JsonResponse
    {
        $callingUserId = $request->user()->id;

        Schema::disableForeignKeyConstraints();

        $relatedTables = ['acknowledgments', 'shift_drop_volunteers', 'shift_drops', 'schedule_entries', 'time_off_requests'];
        foreach ($relatedTables as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }

        User::where('id', '!=', $callingUserId)->delete();

        Schema::enableForeignKeyConstraints();

        return response()->json(['message' => 'All employees have been removed.']);
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
