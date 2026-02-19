<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `menu_items` table.
 *
 * The canonical menu for each location. Menu items can be linked to 86'd entries,
 * specials, and push items via foreign keys. Each item belongs to a location and
 * optionally to a category.
 *
 * Key columns:
 *   - location_id (FK) -- The owning location. Cascade-deletes with the location.
 *   - category_id (FK, nullable) -- Optional grouping. Set to null if the
 *     category is deleted (nullOnDelete).
 *   - name        (string) -- Item name shown to staff and guests.
 *   - description (text, nullable) -- Ingredients, preparation notes, etc.
 *   - price       (decimal 8,2, nullable) -- Menu price in dollars.
 *   - type        (enum: food, drink, both) -- Classification for filtering.
 *   - is_new      (boolean, default false) -- Flags recently added items for
 *     a "new" badge in the UI.
 *   - is_active   (boolean, default true) -- Soft toggle to hide items without
 *     deleting them.
 *   - allergens   (JSON, nullable) -- Array of allergen tags (e.g., ["gluten", "dairy"]).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->enum('type', ['food', 'drink', 'both']);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('allergens')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
