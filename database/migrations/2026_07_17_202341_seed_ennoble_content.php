<?php

use Database\Seeders\AchievementDefinitionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Games and their levels are seeded by the Word Match / Quick Math
     * migration; the badge catalogue is overall-scope and has no game
     * dependency, so it can be seeded here.
     */
    public function up(): void
    {
        (new AchievementDefinitionSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('achievements')->delete();
    }
};
