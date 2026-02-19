<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `acknowledgments` table.
 *
 * Polymorphic "read receipt" table that records when a user has acknowledged
 * (confirmed they've read) a piece of pre-shift content. The polymorphic columns
 * (acknowledgable_type + acknowledgable_id) allow a single table to track
 * acknowledgments for announcements, specials, push items, or any other model.
 *
 * Key columns:
 *   - user_id            (FK -> users) -- The staff member who acknowledged.
 *   - acknowledgable_type (string) -- The fully-qualified model class name
 *     (e.g., "App\Models\Announcement").
 *   - acknowledgable_id  (unsigned big int) -- The ID of the acknowledged record.
 *   - acknowledged_at    (timestamp) -- When the user confirmed the read.
 *
 * A unique composite index on (user_id, acknowledgable_type, acknowledgable_id)
 * prevents duplicate acknowledgments -- a user can only acknowledge a given
 * resource once.
 *
 * Note: This table intentionally has no `id` auto-increment or timestamps columns
 * beyond acknowledged_at, keeping it lightweight for high-volume inserts.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('acknowledgable_type');
            $table->unsignedBigInteger('acknowledgable_id');
            $table->timestamp('acknowledged_at');

            $table->unique(['user_id', 'acknowledgable_type', 'acknowledgable_id'], 'acknowledgments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acknowledgments');
    }
};
