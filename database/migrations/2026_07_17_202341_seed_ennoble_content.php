<?php

use Database\Seeders\AchievementDefinitionSeeder;
use Database\Seeders\GameDefinitionSeeder;
use Database\Seeders\GameLevelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new GameDefinitionSeeder)->run();
        (new GameLevelSeeder)->run();
        (new AchievementDefinitionSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('achievements')->whereIn('slug', [
            'first-step',
            'steady-three',
            'precision-minded',
            'signal-momentum',
            'signal-master',
            'clear-without-hints',
        ])->delete();

        $gameIds = DB::table('games')
            ->whereIn('slug', ['signal-shift', 'clear-thought'])
            ->pluck('id');

        DB::table('game_levels')
            ->whereIn('game_id', $gameIds)
            ->whereIn('difficulty', ['beginner', 'intermediate', 'advanced'])
            ->delete();
        DB::table('games')
            ->whereIn('id', $gameIds)
            ->delete();
    }
};
