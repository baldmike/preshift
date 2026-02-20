<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `time_off_requests` table.
 *
 * Staff submit time-off requests specifying a date range and an optional
 * reason. Managers approve or deny requests. Approved time-off is displayed
 * in the schedule builder so managers can avoid scheduling conflicts.
 *
 * Status flow: pending → approved/denied
 *
 * Key columns:
 *   - user_id     (FK) — The staff member requesting time off.
 *   - location_id (FK) — The location for scoping/visibility.
 *   - start_date  (date) — First day of requested time off.
 *   - end_date    (date) — Last day of requested time off.
 *   - reason      (string, nullable) — Optional explanation.
 *   - status      (enum) — Current state (pending, approved, denied).
 *   - resolved_by (FK, nullable) — Manager who made the decision.
 *   - resolved_at (timestamp, nullable) — When the decision was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_requests');
    }
};
