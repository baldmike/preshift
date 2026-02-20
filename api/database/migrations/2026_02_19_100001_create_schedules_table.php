<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `schedules` table.
 *
 * A schedule represents one week of shifts at a location. Each schedule is
 * identified by its location + week_start (Monday). Schedules start in "draft"
 * status; once the manager is happy with the assignments they publish it,
 * making shifts visible to staff. A unique constraint prevents duplicate
 * schedules for the same week at the same location.
 *
 * Key columns:
 *   - location_id  (FK) — The location this schedule belongs to.
 *   - week_start   (date) — Monday of the target week.
 *   - status       (enum) — "draft" while being built, "published" once released.
 *   - published_at (timestamp, nullable) — When the schedule was last published.
 *   - published_by (FK → users, nullable) — The manager who published it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->date('week_start');                          // Monday of the week
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();       // null while still a draft
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Prevent two schedules for the same week at the same location
            $table->unique(['location_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
