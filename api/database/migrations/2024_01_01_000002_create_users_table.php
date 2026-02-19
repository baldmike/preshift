<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `users` table.
 *
 * Stores all staff accounts. Each user belongs to a single location (except admins,
 * whose location_id is null, granting them access to all locations). The role enum
 * drives authorization throughout the app: admins and managers can create/edit
 * content, while servers and bartenders have read-only access to pre-shift data.
 *
 * Key columns:
 *   - location_id (FK, nullable) -- The location this user belongs to. Null for
 *     admins who oversee all locations. Set to null on delete (nullOnDelete).
 *   - email       (string, unique) -- Login identifier.
 *   - password    (string) -- Bcrypt-hashed password.
 *   - role        (enum: admin, manager, server, bartender) -- Determines
 *     permissions via the CheckRole middleware.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'server', 'bartender']);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
