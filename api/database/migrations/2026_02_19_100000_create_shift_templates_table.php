<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `shift_templates` table.
 *
 * Shift templates are reusable shift definitions for a location — e.g. "Lunch"
 * (10:30 AM – 3:00 PM), "Dinner" (4:00 PM – 11:00 PM). Managers define these
 * once and then assign staff to template+date combinations when building a
 * weekly schedule.
 *
 * Key columns:
 *   - location_id (FK) — The owning location. Cascade-deletes with the location.
 *   - name        (string) — Short label for the shift (e.g. "Brunch", "Double").
 *   - start_time  (time) — When the shift begins (24-hour format, no date component).
 *   - end_time    (time) — When the shift ends.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('name');                // e.g. "Lunch", "Dinner", "Double"
            $table->time('start_time');            // e.g. 10:30:00
            $table->time('end_time');              // e.g. 15:00:00
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};
