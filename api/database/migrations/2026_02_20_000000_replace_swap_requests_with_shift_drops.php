<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('swap_requests');

        Schema::create('shift_drops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['open', 'filled', 'cancelled'])->default('open');
            $table->foreignId('filled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shift_drop_volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_drop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('selected')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['shift_drop_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_drop_volunteers');
        Schema::dropIfExists('shift_drops');

        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('target_user_id')->nullable()->constrained('users');
            $table->foreignId('picked_up_by')->nullable()->constrained('users');
            $table->string('status')->default('pending');
            $table->string('reason', 255)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }
};
