<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the Vertex game to existing databases. The earlier games migrations
     * already ran on installed devices, so they won't re-invoke the seeder —
     * this migration re-runs the (now six-game) seeder, which upserts, leaving
     * the existing games untouched while adding Vertex + its levels.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded vertex game_levels rows.
        DB::table('games')->where('slug', 'vertex')->delete();
    }
};
