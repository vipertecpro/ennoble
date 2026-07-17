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
        Schema::create('daily_workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->date('workout_date');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('generation_version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('statistics_recorded_at')->nullable();
            $table->unsignedInteger('training_seconds')->default(0);
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'workout_date']);
            $table->index(['profile_id', 'status', 'workout_date']);
        });

        Schema::create('daily_workout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->restrictOnDelete();
            $table->foreignId('game_level_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('status')->default('pending');
            $table->json('configuration');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['daily_workout_id', 'position']);
            $table->unique(['daily_workout_id', 'game_id']);
            $table->index(['daily_workout_id', 'status']);
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->restrictOnDelete();
            $table->foreignId('game_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('daily_workout_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('in_progress');
            $table->string('mode')->nullable();
            $table->unsignedSmallInteger('snapshot_version')->default(1);
            $table->unsignedSmallInteger('current_round')->default(0);
            $table->json('state_snapshot')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedInteger('average_response_ms')->nullable();
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('incorrect_count')->default(0);
            $table->unsignedSmallInteger('missed_count')->default(0);
            $table->unsignedSmallInteger('hint_count')->default(0);
            $table->unsignedSmallInteger('best_combo')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_interaction_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('statistics_recorded_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'game_id', 'status', 'started_at']);
            $table->index(['daily_workout_item_id', 'status']);
            $table->index(['game_id', 'score']);
        });

        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->string('outcome');
            $table->unsignedInteger('response_ms')->nullable();
            $table->integer('score_delta')->default(0);
            $table->unsignedSmallInteger('combo')->nullable();
            $table->boolean('hint_used')->default(false);
            $table->json('response')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'round_number']);
            $table->index(['challenge_id', 'outcome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('daily_workout_items');
        Schema::dropIfExists('daily_workouts');
    }
};
