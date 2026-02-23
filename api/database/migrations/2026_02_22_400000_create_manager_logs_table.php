<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `manager_logs` table.
 *
 * Each row represents a single daily operational log entry for a location.
 * Managers write freeform notes (`body`) and the system auto-snapshots
 * weather, events, and scheduled staff as immutable JSON columns at
 * creation time.
 *
 * Unique constraint on (location_id, log_date) enforces one log per day
 * per location. An index on log_date supports efficient date-range queries.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manager_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('log_date');
            $table->text('body');
            $table->json('weather_snapshot')->nullable();
            $table->json('events_snapshot')->nullable();
            $table->json('schedule_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'log_date']);
            $table->index('log_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_logs');
    }
};
