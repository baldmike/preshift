<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique composite index on (user_id, date) to the schedule_entries
 * table. This prevents a user from being scheduled for more than one shift
 * on the same day, enforced at the database level.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique index on the user_id + date combination so the database
     * rejects any attempt to assign the same person to two shifts on one day.
     */
    public function up(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->unique(['user_id', 'date'], 'schedule_entries_user_id_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the unique index, allowing duplicate user+date entries again.
     */
    public function down(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->dropUnique('schedule_entries_user_id_date_unique');
        });
    }
};
