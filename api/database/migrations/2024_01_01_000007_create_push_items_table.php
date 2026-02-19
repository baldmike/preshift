<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `push_items` table.
 *
 * Push items are menu items that management wants staff to actively upsell or
 * suggest to guests. Reasons might include new launches, high-margin items, or
 * overstock situations. Each push item has a priority level so staff can focus on
 * the most important upsells first.
 *
 * Key columns:
 *   - location_id  (FK) -- The owning location. Cascade-deletes with the location.
 *   - menu_item_id (FK, nullable) -- The menu item to push; null for general pushes.
 *   - title        (string) -- Short directive (e.g., "Push Espresso Martinis").
 *   - description  (text, nullable) -- Talking points or context for staff.
 *   - reason       (string, nullable) -- Why this item needs pushing (e.g., "Overstock").
 *   - priority     (enum: low, medium, high) -- Visual priority ranking in the UI.
 *   - is_active    (boolean, default true) -- Soft toggle without deleting.
 *   - created_by   (FK -> users) -- The manager who created the push directive.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('reason')->nullable();
            $table->enum('priority', ['low', 'medium', 'high']);
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
        Schema::dropIfExists('push_items');
    }
};
