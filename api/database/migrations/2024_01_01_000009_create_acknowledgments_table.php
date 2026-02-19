<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('acknowledgable_type');
            $table->unsignedBigInteger('acknowledgable_id');
            $table->timestamp('acknowledged_at');

            $table->unique(['user_id', 'acknowledgable_type', 'acknowledgable_id'], 'acknowledgments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acknowledgments');
    }
};
