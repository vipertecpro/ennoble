<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Enums\SkillKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the two offline games (Word Match, Quick Math) with their per-difficulty
 * levels. Idempotent: safe on fresh installs and on upgrades of existing
 * on-device databases.
 */
class WordMatchQuickMathSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $games = [
            [
                'type' => GameType::WordMatch->value,
                'slug' => 'word-match',
                'name' => 'Word Match',
                'description' => 'Match each word to its meaning before the timer runs out.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 1,
                'skill_keys' => [SkillKey::Clarity->value, SkillKey::CriticalReading->value],
                'configuration' => ['content_version' => 1],
            ],
            [
                'type' => GameType::QuickMath->value,
                'slug' => 'quick-math',
                'name' => 'Quick Math',
                'description' => 'Solve fast-fire arithmetic and keep your streak alive.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 2,
                'skill_keys' => [SkillKey::Speed->value, SkillKey::Precision->value, SkillKey::Focus->value],
                'configuration' => ['content_version' => 1],
            ],
            [
                'type' => GameType::Recall->value,
                'slug' => 'recall',
                'name' => 'Recall',
                'description' => 'Watch the sequence light up, then tap it back from memory.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 3,
                'skill_keys' => [SkillKey::Focus->value, SkillKey::Structure->value, SkillKey::Adaptability->value],
                'configuration' => ['content_version' => 1],
            ],
            [
                'type' => GameType::Flow->value,
                'slug' => 'flow',
                'name' => 'Flow',
                'description' => 'Ride the current — swipe with each surge of light before it reaches you.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 4,
                'skill_keys' => [SkillKey::Speed->value, SkillKey::Focus->value, SkillKey::Adaptability->value],
                'configuration' => ['content_version' => 1],
            ],
            [
                'type' => GameType::Signal->value,
                'slug' => 'signal',
                'name' => 'Signal',
                'description' => 'Trust the ink, not the word — then watch the rule flip.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 5,
                'skill_keys' => [SkillKey::Focus->value, SkillKey::Precision->value, SkillKey::Adaptability->value],
                'configuration' => ['content_version' => 1],
            ],
            [
                'type' => GameType::Vertex->value,
                'slug' => 'vertex',
                'name' => 'Barrage',
                'description' => 'Waves descend — clear every target in the formation and hold fire on the rest.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 6,
                'skill_keys' => [SkillKey::Focus->value, SkillKey::Precision->value, SkillKey::Speed->value],
                'configuration' => ['content_version' => 1],
            ],
        ];

        DB::table('games')->upsert(
            array_map(static fn (array $game): array => [
                ...$game,
                'skill_keys' => json_encode($game['skill_keys'], JSON_THROW_ON_ERROR),
                'configuration' => json_encode($game['configuration'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ], $games),
            ['slug'],
            ['type', 'name', 'description', 'status', 'sort_order', 'skill_keys', 'configuration', 'updated_at'],
        );

        $gameIds = DB::table('games')
            ->whereIn('slug', ['word-match', 'quick-math', 'recall', 'flow', 'signal', 'vertex'])
            ->pluck('id', 'slug');

        $levels = [
            'word-match' => [
                [Difficulty::Beginner, 'Warm-up', 8, 4000, ['seconds_per_round' => 8, 'options_count' => 4, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 10, 3200, ['seconds_per_round' => 7, 'options_count' => 4, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 12, 2600, ['seconds_per_round' => 6, 'options_count' => 4, 'lives' => 3]],
            ],
            'quick-math' => [
                [Difficulty::Beginner, 'Warm-up', 10, 4000, ['seconds_per_round' => 8, 'operations' => ['add', 'subtract'], 'operand_range' => ['min' => 2, 'max' => 10], 'options_count' => 4, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 12, 3000, ['seconds_per_round' => 6, 'operations' => ['add', 'subtract', 'multiply'], 'operand_range' => ['min' => 2, 'max' => 12], 'options_count' => 4, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 14, 2400, ['seconds_per_round' => 5, 'operations' => ['add', 'subtract', 'multiply', 'divide'], 'operand_range' => ['min' => 3, 'max' => 15], 'options_count' => 4, 'lives' => 3]],
            ],
            'recall' => [
                [Difficulty::Beginner, 'Warm-up', 8, 5000, ['tiles' => 6, 'start_length' => 3, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 10, 4000, ['tiles' => 9, 'start_length' => 3, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 12, 3200, ['tiles' => 9, 'start_length' => 4, 'lives' => 3]],
            ],
            'flow' => [
                [Difficulty::Beginner, 'Warm-up', 12, 1400, ['directions' => ['left', 'right'], 'window_ms' => 2400, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 14, 1100, ['directions' => ['left', 'right', 'up'], 'window_ms' => 1900, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 16, 900, ['directions' => ['left', 'right', 'up', 'down'], 'window_ms' => 1500, 'lives' => 3]],
            ],
            'signal' => [
                [Difficulty::Beginner, 'Warm-up', 10, 3500, ['colors' => ['red', 'blue', 'green'], 'rules' => ['ink'], 'options_count' => 3, 'seconds_per_round' => 6, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 12, 2800, ['colors' => ['red', 'blue', 'green', 'purple'], 'rules' => ['ink', 'word'], 'options_count' => 4, 'seconds_per_round' => 5, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 14, 2200, ['colors' => ['red', 'blue', 'green', 'purple', 'pink', 'teal'], 'rules' => ['ink', 'word'], 'options_count' => 4, 'seconds_per_round' => 4, 'lives' => 3]],
            ],
            'vertex' => [
                [Difficulty::Beginner, 'Warm-up', 8, 5000, ['shapes' => ['disc', 'block', 'ring'], 'colours' => ['blue', 'amber', 'rose'], 'forms' => ['shape', 'colour'], 'formation' => 6, 'descent_ms' => 8000, 'lives' => 3]],
                [Difficulty::Intermediate, 'Steady', 10, 4200, ['shapes' => ['disc', 'block', 'bar', 'ring'], 'colours' => ['blue', 'amber', 'violet', 'rose'], 'forms' => ['shape', 'colour', 'not_shape'], 'formation' => 9, 'descent_ms' => 6500, 'lives' => 3]],
                [Difficulty::Advanced, 'Sharp', 12, 3600, ['shapes' => ['disc', 'block', 'bar', 'ring'], 'colours' => ['blue', 'amber', 'violet', 'rose'], 'forms' => ['shape', 'colour', 'not_shape', 'not_colour', 'both'], 'formation' => 12, 'descent_ms' => 5500, 'lives' => 3]],
            ],
        ];

        $rows = [];

        foreach ($levels as $slug => $definitions) {
            $gameId = $gameIds[$slug];

            foreach ($definitions as [$difficulty, $name, $roundCount, $targetResponseMs, $configuration]) {
                $rows[] = [
                    'game_id' => $gameId,
                    'difficulty' => $difficulty->value,
                    'name' => $name,
                    'round_count' => $roundCount,
                    'target_response_ms' => $targetResponseMs,
                    'configuration' => json_encode(['content_version' => 1, ...$configuration], JSON_THROW_ON_ERROR),
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
