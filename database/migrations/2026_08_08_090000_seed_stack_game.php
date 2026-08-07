<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add Stack to existing databases. On-device there is no db:seed —
     * migrations are the only thing that runs on an installed app — and the
     * earlier games migrations are already recorded there, so they will never
     * re-invoke the seeder. This re-runs it; the seeder upserts, so games
     * already installed are untouched and Stack is added alongside them.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded stack game_levels rows.
        DB::table('games')->where('slug', 'stack')->delete();
    }
};
