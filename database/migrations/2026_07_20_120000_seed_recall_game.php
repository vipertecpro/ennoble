<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the Recall game to existing databases. The original games migration
     * already ran on installed devices, so it won't re-invoke the seeder — this
     * migration re-runs the (now three-game) seeder, which upserts, leaving Word
     * Match and Quick Math untouched while adding Recall + its levels.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded recall game_levels rows.
        DB::table('games')->where('slug', 'recall')->delete();
    }
};
