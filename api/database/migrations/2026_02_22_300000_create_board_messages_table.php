<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `board_messages` table.
 *
 * Board messages form a location-scoped bulletin board where all staff can
 * post updates and have threaded conversations. Top-level posts have a null
 * `parent_id`; replies reference the parent post's ID.
 *
 * Key columns:
 *   - location_id  (FK locations) -- scopes the message to one venue
 *   - user_id      (FK users)     -- the author of the message
 *   - parent_id    (nullable FK self) -- null for top-level posts, set for replies
 *   - body         (text)         -- the message content
 *   - visibility   (enum)         -- 'all' or 'managers'; controls who can see the post
 *   - pinned       (boolean)      -- pinned posts float to the top of the board
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('board_messages')->cascadeOnDelete();
            $table->text('body');
            $table->enum('visibility', ['all', 'managers'])->default('all');
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->index(['location_id', 'parent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_messages');
    }
};
