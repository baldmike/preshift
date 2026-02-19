<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `personal_access_tokens` table.
 *
 * Required by Laravel Sanctum for API token authentication. When a user logs in
 * via POST /api/login, a new personal access token is created and returned to the
 * client. The client includes this token as a Bearer token in the Authorization
 * header on subsequent requests. Sanctum validates the token against this table.
 *
 * Key columns:
 *   - tokenable_type / tokenable_id (morph) -- Polymorphic link to the User model.
 *   - name          (text) -- A label for the token (e.g., "api-token").
 *   - token         (string, unique) -- SHA-256 hash of the plain-text token.
 *   - abilities     (text, nullable) -- JSON-encoded array of token abilities/scopes.
 *   - last_used_at  (timestamp, nullable) -- Updated on each authenticated request.
 *   - expires_at    (timestamp, nullable) -- Optional token expiration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
