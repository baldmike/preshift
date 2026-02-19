<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `specials` table.
 *
 * Stores promotional specials for a location (e.g., happy-hour discounts, weekly
 * drink deals). Each special has a recurrence type, an active date range, and an
 * optional link to a menu item. Specials appear on the pre-shift dashboard so
 * staff know what to promote during their shift.
 *
 * Key columns:
 *   - location_id  (FK) -- The owning location. Cascade-deletes with the location.
 *   - menu_item_id (FK, nullable) -- The menu item the special applies to.
 *   - title        (string) -- Short headline (e.g., "Half-Price Wings").
 *   - description  (text, nullable) -- Details for staff (hours, conditions, etc.).
 *   - type         (enum: daily, weekly, monthly, limited_time) -- Recurrence cadence.
 *   - starts_at    (date) -- The first day the special is active.
 *   - ends_at      (date, nullable) -- The last day; null means open-ended.
 *   - is_active    (boolean, default true) -- Soft toggle without deleting.
 *   - created_by   (FK -> users) -- The manager who created the special.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('specials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['daily', 'weekly', 'monthly', 'limited_time']);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specials');
    }
};
