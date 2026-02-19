<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `categories` table.
 *
 * Categories group menu items within a location (e.g., Appetizers, Entrees,
 * Drinks, Desserts). Each category belongs to a location and has a sort_order
 * integer for controlling display sequence in the UI.
 *
 * Key columns:
 *   - location_id (FK) -- The owning location. Cascade-deletes with the location.
 *   - name        (string) -- Display name (e.g., "Appetizers").
 *   - sort_order  (integer, default 0) -- Determines the order categories appear
 *     in the menu; lower numbers display first.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
