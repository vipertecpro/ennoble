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
        Schema::create('progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('skill_key');
            $table->unsignedSmallInteger('score_before');
            $table->unsignedSmallInteger('score_after');
            $table->smallInteger('delta');
            $table->unsignedInteger('evidence_count');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['game_session_id', 'skill_key']);
            $table->index(['profile_id', 'skill_key', 'recorded_at']);
        });

        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('scope_key');
            $table->unsignedInteger('sessions_completed')->default(0);
            $table->unsignedInteger('workouts_completed')->default(0);
            $table->unsignedBigInteger('training_seconds')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('attempted_count')->default(0);
            $table->unsignedBigInteger('total_response_ms')->default(0);
            $table->unsignedInteger('response_count')->default(0);
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedInteger('average_response_ms')->nullable();
            $table->unsignedInteger('best_score')->nullable();
            $table->unsignedSmallInteger('longest_combo')->default(0);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_workout_date')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'scope_key']);
            $table->index(['profile_id', 'game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
        Schema::dropIfExists('progress_snapshots');
    }
};
