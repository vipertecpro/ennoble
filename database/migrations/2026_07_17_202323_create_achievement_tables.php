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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('type');
            $table->json('criterion');
            $table->unsignedSmallInteger('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('achievement_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('daily_workout_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('unlocked_at');
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'achievement_id']);
            $table->index(['profile_id', 'unlocked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_unlocks');
        Schema::dropIfExists('achievements');
    }
};
