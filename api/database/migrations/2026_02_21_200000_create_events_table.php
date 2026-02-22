<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `events` table.
 *
 * Events are simple daily happenings posted by managers for staff awareness
 * (e.g. "Wine tasting at 7pm", "Private party in back room 6-9"). They appear
 * on the pre-shift dashboard alongside 86'd items, specials, and announcements.
 *
 * Key columns:
 *   - location_id  (FK) -- The owning location. Cascade-deletes with the location.
 *   - title        (string) -- Short headline for the event.
 *   - description  (text, nullable) -- Optional additional details.
 *   - event_date   (date) -- The day the event applies to.
 *   - event_time   (string(5), nullable) -- Optional "HH:MM" display time.
 *   - created_by   (FK -> users) -- The manager who created the event.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->string('event_time', 5)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
