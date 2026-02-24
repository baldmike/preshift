<?php

/**
 * Backfill the `location_user` pivot table from existing `users` records.
 *
 * Copies every user's current location_id + role into the pivot table so
 * that existing single-location users automatically have a membership row.
 * Uses insertOrIgnore so the migration is idempotent — safe to run multiple
 * times without creating duplicates.
 *
 * Only processes users that have a non-null location_id.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')
            ->whereNotNull('location_id')
            ->select('id', 'location_id', 'role')
            ->get();

        foreach ($users as $user) {
            DB::table('location_user')->insertOrIgnore([
                'user_id'     => $user->id,
                'location_id' => $user->location_id,
                'role'        => $user->role,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncate the pivot table — the forward migration can re-populate it
        DB::table('location_user')->truncate();
    }
};
