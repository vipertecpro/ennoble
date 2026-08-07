<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the Axis game to existing databases. On-device there is no db:seed —
     * migrations are the only thing that runs on an installed app — and the
     * earlier games migrations have already been recorded there, so they will
     * never re-invoke the seeder. This migration re-runs the (now seven-game)
     * seeder, which upserts: existing games and levels are left untouched and
     * Axis is added alongside them.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded axis game_levels rows.
        DB::table('games')->where('slug', 'axis')->delete();
    }
};
