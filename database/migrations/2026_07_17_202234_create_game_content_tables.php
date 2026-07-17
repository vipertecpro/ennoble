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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('status')->default('playable');
            $table->unsignedSmallInteger('sort_order');
            $table->json('skill_keys');
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('game_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('difficulty');
            $table->string('name');
            $table->unsignedSmallInteger('round_count');
            $table->unsignedInteger('target_response_ms')->nullable();
            $table->json('configuration');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['game_id', 'difficulty']);
            $table->index(['game_id', 'is_active']);
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_level_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('mode');
            $table->unsignedSmallInteger('content_version')->default(1);
            $table->text('prompt');
            $table->json('payload');
            $table->json('accepted_answers');
            $table->text('explanation');
            $table->text('hint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['game_id', 'slug']);
            $table->index(['game_id', 'mode', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('game_levels');
        Schema::dropIfExists('games');
    }
};
