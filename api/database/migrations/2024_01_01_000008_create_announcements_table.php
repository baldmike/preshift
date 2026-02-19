<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `announcements` table.
 *
 * Announcements are manager-to-staff messages displayed on the pre-shift dashboard.
 * They can be targeted at specific roles (e.g., only bartenders) and can expire
 * automatically. Priority levels (normal, important, urgent) control visual
 * prominence in the UI.
 *
 * Key columns:
 *   - location_id  (FK) -- The owning location. Cascade-deletes with the location.
 *   - title        (string) -- Short headline for the announcement.
 *   - body         (text) -- Full message content.
 *   - priority     (enum: normal, important, urgent) -- Controls visual styling and
 *     sort order; urgent announcements are highlighted prominently.
 *   - target_roles (JSON, nullable) -- Array of role strings (e.g., ["bartender"]).
 *     Null means the announcement is visible to all roles.
 *   - posted_by    (FK -> users) -- The manager who authored the announcement.
 *   - expires_at   (timestamp, nullable) -- Auto-hides the announcement after this
 *     time; null means it never expires.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('priority', ['normal', 'important', 'urgent']);
            $table->json('target_roles')->nullable();
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
