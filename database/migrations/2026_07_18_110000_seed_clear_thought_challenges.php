<?php

use Database\Seeders\ClearThoughtChallengeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the bundled original Clear Thought editorial content.
     */
    public function up(): void
    {
        (new ClearThoughtChallengeSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $gameId = DB::table('games')->where('slug', 'clear-thought')->value('id');

        if ($gameId === null) {
            return;
        }

        DB::table('challenges')
            ->where('game_id', $gameId)
            ->where('slug', 'like', 'ct-%')
            ->whereNotIn('id', function ($query): void {
                $query->select('challenge_id')
                    ->from('game_rounds')
                    ->whereNotNull('challenge_id');
            })
            ->delete();
    }
};
