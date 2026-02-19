<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `locations` table.
 *
 * Locations represent individual restaurant venues (e.g., "The Anchor - Downtown").
 * They are the top-level tenant in the system: every other resource (users, menu
 * items, specials, announcements, etc.) belongs to a location. Each location has
 * a name, optional address, and a timezone used for date/time display and special
 * scheduling.
 *
 * Key columns:
 *   - name     (string)  -- Display name of the venue.
 *   - address  (string, nullable) -- Physical address for reference.
 *   - timezone (string, default 'America/New_York') -- IANA timezone identifier
 *     used for localizing timestamps and scheduling specials.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('timezone', 50)->default('America/New_York');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
