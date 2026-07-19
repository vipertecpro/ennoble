<?php

use App\Enums\GameStatus;
use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the v2 offline games (Word Match, Quick Math) and retire the v1
     * reference games on both fresh installs and existing on-device databases.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        DB::table('games')
            ->whereIn('slug', ['signal-shift', 'clear-thought'])
            ->update(['status' => GameStatus::Playable->value, 'updated_at' => now()]);

        // Cascades to the seeded game_levels rows.
        DB::table('games')
            ->whereIn('slug', ['word-match', 'quick-math'])
            ->delete();
    }
};
