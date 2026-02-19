<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `eighty_sixed` table.
 *
 * Tracks items that are currently unavailable ("86'd") at a location. An item can
 * be linked to a menu_item_id or entered as free text (e.g., "Oat Milk") when the
 * unavailable ingredient is not a standalone menu item. Items are restored by
 * setting the `restored_at` timestamp rather than deleting the row, preserving an
 * audit trail of what was 86'd and when it came back.
 *
 * Key columns:
 *   - location_id    (FK) -- The location where the item is unavailable.
 *   - menu_item_id   (FK, nullable) -- Optional link to a menu item; null for
 *     free-text entries.
 *   - item_name      (string) -- The display name (denormalized from menu_items
 *     or manually entered).
 *   - reason         (string, nullable) -- Why the item is 86'd.
 *   - eighty_sixed_by (FK -> users) -- The user who flagged the item.
 *   - restored_at    (timestamp, nullable) -- Null while 86'd; set when restored.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eighty_sixed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('reason')->nullable();
            $table->foreignId('eighty_sixed_by')->constrained('users');
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eighty_sixed');
    }
};
