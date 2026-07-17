<?php

namespace Database\Seeders;

use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementDefinitionSeeder extends Seeder
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
            throw new ModelNotFoundException('Both Ennoble game definitions must exist before achievements are seeded.');
        }

        $now = now();
        $achievements = [
            ['first-step', 'First Step', 'Complete your first daily workout.', AchievementType::FirstWorkout, null, ['workouts' => 1], 1],
            ['steady-three', 'Steady Three', 'Complete a daily workout on three consecutive days.', AchievementType::WorkoutStreak, null, ['days' => 3], 2],
            ['precision-minded', 'Precision Minded', 'Reach at least 90% overall accuracy.', AchievementType::Accuracy, null, ['accuracy' => 90], 3],
            ['signal-momentum', 'Signal Momentum', 'Build a combo of eight in Signal Shift.', AchievementType::Combo, 'signal-shift', ['combo' => 8], 4],
            ['signal-master', 'Signal Master', 'Score 1,500 points in Signal Shift.', AchievementType::Score, 'signal-shift', ['score' => 1500], 5],
            ['clear-without-hints', 'Clear Without Hints', 'Complete a Clear Thought session accurately without a hint.', AchievementType::HintFree, 'clear-thought', ['minimum_correct' => 1], 6],
        ];

        $rows = array_map(
            static fn (array $achievement): array => [
                'slug' => $achievement[0],
                'name' => $achievement[1],
                'description' => $achievement[2],
                'type' => $achievement[3]->value,
                'game_id' => $achievement[4] === null ? null : $gameIds[$achievement[4]],
                'criterion' => json_encode($achievement[5], JSON_THROW_ON_ERROR),
                'sort_order' => $achievement[6],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $achievements,
        );

        DB::table('achievements')->upsert(
            $rows,
            ['slug'],
            ['game_id', 'name', 'description', 'type', 'criterion', 'sort_order', 'is_active', 'updated_at'],
        );
    }
}
