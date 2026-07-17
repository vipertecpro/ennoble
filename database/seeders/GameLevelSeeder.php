<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gameIds = DB::table('games')
            ->whereIn('slug', ['signal-shift', 'clear-thought'])
            ->pluck('id', 'slug');

        if ($gameIds->count() !== 2) {
            throw new ModelNotFoundException('Both Ennoble game definitions must exist before levels are seeded.');
        }

        $now = now();
        $levels = [
            'signal-shift' => [
                [Difficulty::Beginner, 'Measured', 6, 3200, ['targets' => 3, 'distractors' => 1]],
                [Difficulty::Intermediate, 'Steady', 8, 2500, ['targets' => 4, 'distractors' => 2]],
                [Difficulty::Advanced, 'Agile', 10, 1900, ['targets' => 5, 'distractors' => 3]],
            ],
            'clear-thought' => [
                [Difficulty::Beginner, 'Direct', 5, null, ['max_attempts' => 2]],
                [Difficulty::Intermediate, 'Refined', 6, null, ['max_attempts' => 2]],
                [Difficulty::Advanced, 'Exact', 7, null, ['max_attempts' => 1]],
            ],
        ];

        $rows = [];

        foreach ($levels as $gameSlug => $gameLevels) {
            foreach ($gameLevels as [$difficulty, $name, $roundCount, $targetResponseMs, $configuration]) {
                $rows[] = [
                    'game_id' => $gameIds[$gameSlug],
                    'difficulty' => $difficulty->value,
                    'name' => $name,
                    'round_count' => $roundCount,
                    'target_response_ms' => $targetResponseMs,
                    'configuration' => json_encode($configuration, JSON_THROW_ON_ERROR),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('game_levels')->upsert(
            $rows,
            ['game_id', 'difficulty'],
            ['name', 'round_count', 'target_response_ms', 'configuration', 'is_active', 'updated_at'],
        );
    }
}
