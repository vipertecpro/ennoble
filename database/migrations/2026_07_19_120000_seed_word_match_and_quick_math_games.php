<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the offline games (Word Match, Quick Math) with their levels on both
     * fresh installs and existing on-device databases.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Cascades to the seeded game_levels rows.
        DB::table('games')
            ->whereIn('slug', ['word-match', 'quick-math'])
            ->delete();
    }
};
