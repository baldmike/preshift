<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `swap_requests` table.
 *
 * A swap request is initiated by a staff member who wants to give up one of
 * their scheduled shifts. The request can optionally target a specific person
 * or be left open for anyone eligible. Another staff member can "offer" to
 * pick it up, and a manager then approves or denies the swap. On approval the
 * schedule entry is updated to reflect the new assignee.
 *
 * Status flow: pending → offered → approved/denied  (or cancelled at any point)
 *
 * Key columns:
 *   - schedule_entry_id (FK) — The shift being swapped.
 *   - requested_by      (FK) — The staff member requesting the swap.
 *   - target_user_id    (FK, nullable) — Specific person requested, or null for open.
 *   - picked_up_by      (FK, nullable) — The person who offered to take the shift.
 *   - status            (enum) — Current state in the swap lifecycle.
 *   - reason            (string, nullable) — Why the staff member needs to swap.
 *   - resolved_by       (FK, nullable) — Manager who approved/denied.
 *   - resolved_at       (timestamp, nullable) — When the decision was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_entry_id')->constrained('schedule_entries')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('picked_up_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'offered', 'approved', 'denied', 'cancelled'])->default('pending');
            $table->string('reason', 255)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
    }
};
