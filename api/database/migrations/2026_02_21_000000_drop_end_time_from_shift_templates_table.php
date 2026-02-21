<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the `end_time` column from the shift_templates table.
 *
 * Shift names ("Lunch", "Dinner") already communicate what the shift is,
 * and rigid end times don't reflect how restaurant shifts actually work.
 * Templates now only store `start_time`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->time('end_time')->after('start_time');
        });
    }
};
