<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `conversations` and `conversation_user` tables.
 *
 * Conversations represent private 1-on-1 direct message threads between two
 * staff members at the same location. The pivot table `conversation_user`
 * tracks each participant and their `last_read_at` timestamp for unread
 * message detection.
 *
 * Key columns (conversations):
 *   - location_id  (FK locations) -- scopes the conversation to one venue
 *
 * Key columns (conversation_user):
 *   - conversation_id (FK conversations) -- the conversation being joined
 *   - user_id         (FK users)         -- the participating user
 *   - last_read_at    (nullable timestamp) -- when the user last read messages
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};
