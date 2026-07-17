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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key')->default('local')->unique();
            $table->string('display_name');
            $table->string('training_goal')->default('balanced');
            $table->string('difficulty_preference')->default('intermediate');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme_preference')->default('system');
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('haptics_enabled')->default(true);
            $table->boolean('reduced_motion')->default(false);
            $table->boolean('daily_reminder_enabled')->default(false);
            $table->json('accessibility_preferences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('profiles');
    }
};
