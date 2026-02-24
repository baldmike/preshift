<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds city and state columns to the locations table.
 *
 * These fields are collected during initial setup so the location's
 * city and state can be displayed in the top bar and used for weather
 * geocoding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('state', 50)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['city', 'state']);
        });
    }
};
