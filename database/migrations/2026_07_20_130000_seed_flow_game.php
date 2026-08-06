<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the Flow game to existing databases. The earlier games migrations
     * already ran on installed devices, so they won't re-invoke the seeder —
     * this migration re-runs the (now four-game) seeder, which upserts, leaving
     * the existing games untouched while adding Flow + its levels.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded flow game_levels rows.
        DB::table('games')->where('slug', 'flow')->delete();
    }
};
