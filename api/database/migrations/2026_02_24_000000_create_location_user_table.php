<?php

/**
 * Create the `location_user` pivot table.
 *
 * Stores many-to-many memberships between users and locations. Each row
 * represents one user's role at one establishment. A user can belong to
 * multiple locations with a different role at each.
 *
 * Key columns:
 *   - user_id     (FK → users.id)
 *   - location_id (FK → locations.id)
 *   - role        (string: admin|manager|server|bartender)
 *
 * The UNIQUE constraint on (user_id, location_id) prevents duplicate
 * memberships for the same user at the same location.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('role'); // admin|manager|server|bartender
            $table->timestamps();

            $table->unique(['user_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};
