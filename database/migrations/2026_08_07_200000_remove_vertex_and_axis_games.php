<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retire Barrage (slug `vertex`) and Axis.
     *
     * Their seed migrations are already recorded on installed devices, so
     * deleting those files does not undo anything there — this is what
     * actually removes the rows.
     *
     * ORDER MATTERS. `game_sessions.game_id` is restrictOnDelete, so deleting
     * a game that anyone has played fails outright. Sessions go first (rounds
     * cascade from them), then statistics and achievements, then the game —
     * whose levels cascade. Deleting the game first would abort the migration
     * on exactly the devices where someone actually played it.
     */
    public function up(): void
    {
        $gameIds = DB::table('games')->whereIn('slug', ['vertex', 'axis'])->pluck('id');

        if ($gameIds->isEmpty()) {
            return;
        }

        // game_rounds cascade from game_sessions.
        DB::table('game_sessions')->whereIn('game_id', $gameIds)->delete();
        DB::table('statistics')->whereIn('game_id', $gameIds)->delete();

        // Nullable game_id — a badge scoped to a retired game becomes global
        // rather than being deleted, which would strip it from anyone who
        // already earned it.
        DB::table('achievements')->whereIn('game_id', $gameIds)->update(['game_id' => null]);

        DB::table('games')->whereIn('id', $gameIds)->delete();
    }

    public function down(): void
    {
        // Irreversible by design: the games no longer exist in the seeder, so
        // there is nothing to restore them from.
    }
};
