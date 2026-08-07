<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retire Leap. Its seed migration is already recorded on installed
     * devices, so deleting that file undoes nothing there — this removes the
     * rows.
     *
     * Sessions first: game_sessions.game_id is restrictOnDelete, so deleting a
     * game anyone has played fails, and it fails on exactly the devices where
     * it was played. Rounds cascade from sessions; levels cascade from the
     * game. Achievements are nulled rather than deleted, so a badge someone
     * earned is not taken back.
     */
    public function up(): void
    {
        $gameIds = DB::table('games')->where('slug', 'leap')->pluck('id');

        if ($gameIds->isEmpty()) {
            return;
        }

        DB::table('game_sessions')->whereIn('game_id', $gameIds)->delete();
        DB::table('statistics')->whereIn('game_id', $gameIds)->delete();
        DB::table('achievements')->whereIn('game_id', $gameIds)->update(['game_id' => null]);
        DB::table('games')->whereIn('id', $gameIds)->delete();
    }

    public function down(): void
    {
        // Irreversible: the game is gone from the seeder too.
    }
};
