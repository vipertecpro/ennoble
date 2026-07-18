<?php

use App\Enums\Difficulty;
use Database\Seeders\GameLevelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new GameLevelSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $signalShiftId = DB::table('games')
            ->where('slug', 'signal-shift')
            ->value('id');

        if ($signalShiftId === null) {
            return;
        }

        $previousConfigurations = [
            Difficulty::Beginner->value => ['targets' => 3, 'distractors' => 1],
            Difficulty::Intermediate->value => ['targets' => 4, 'distractors' => 2],
            Difficulty::Advanced->value => ['targets' => 5, 'distractors' => 3],
        ];

        foreach ($previousConfigurations as $difficulty => $configuration) {
            DB::table('game_levels')
                ->where('game_id', $signalShiftId)
                ->where('difficulty', $difficulty)
                ->update([
                    'configuration' => json_encode($configuration, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }
};
