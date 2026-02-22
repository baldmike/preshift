<?php

/**
 * Add a nullable JSON `roles` column to the users table.
 *
 * This enables multi-role staff support — an employee whose primary role is
 * "server" can also hold a secondary "bartender" role (or vice-versa). When
 * `roles` is NULL the application falls back to the existing single `role`
 * column, so no data migration is required.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
