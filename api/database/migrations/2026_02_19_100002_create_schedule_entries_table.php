<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `schedule_entries` table.
 *
 * Each entry represents one staff member assigned to one shift template on a
 * specific date within a schedule. For example: "Jane, Dinner shift, Tuesday
 * Feb 25, role=server". Entries belong to a Schedule, reference a User and a
 * ShiftTemplate, and carry an optional note for the manager (e.g. "training",
 * "cut first").
 *
 * Key columns:
 *   - schedule_id       (FK) — Parent schedule for this entry.
 *   - user_id           (FK) — The staff member being scheduled.
 *   - shift_template_id (FK) — Which shift type (Lunch, Dinner, etc.).
 *   - date              (date) — The specific calendar day for this shift.
 *   - role              (enum) — The role the staff fills for this shift.
 *   - notes             (string, nullable) — Manager notes like "training".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->date('date');                                      // Specific day of the shift
            $table->enum('role', ['server', 'bartender']);             // Role for this shift
            $table->string('notes', 255)->nullable();                  // e.g. "training", "cut first"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};
