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
                [
                    Difficulty::Beginner,
                    'Measured',
                    6,
                    3200,
                    $this->signalShiftConfiguration([
                        [
                            'target_color' => 'teal',
                            'target_shape' => 'circle',
                            'speed_modifier' => 0.75,
                            'spawn_density' => 3,
                            'wave_count' => 2,
                            'seconds_per_wave' => 5,
                        ],
                        [
                            'target_color' => 'gold',
                            'excluded_shape' => 'square',
                            'speed_modifier' => 0.85,
                            'spawn_density' => 3,
                            'wave_count' => 2,
                            'seconds_per_wave' => 5,
                        ],
                        [
                            'motion_required' => true,
                            'target_shape' => 'circle',
                            'speed_modifier' => 0.9,
                            'spawn_density' => 4,
                            'wave_count' => 2,
                            'seconds_per_wave' => 4,
                        ],
                    ]),
                ],
                [
                    Difficulty::Intermediate,
                    'Steady',
                    8,
                    2500,
                    $this->signalShiftConfiguration([
                        [
                            'target_color' => 'teal',
                            'target_shape' => 'circle',
                            'speed_modifier' => 0.95,
                            'spawn_density' => 4,
                            'wave_count' => 2,
                            'seconds_per_wave' => 4,
                        ],
                        [
                            'target_color' => 'gold',
                            'excluded_shape' => 'diamond',
                            'speed_modifier' => 1.0,
                            'spawn_density' => 4,
                            'wave_count' => 3,
                            'seconds_per_wave' => 3,
                        ],
                        [
                            'motion_required' => true,
                            'size_required' => 'large',
                            'speed_modifier' => 1.1,
                            'spawn_density' => 5,
                            'wave_count' => 3,
                            'seconds_per_wave' => 3,
                        ],
                    ]),
                ],
                [
                    Difficulty::Advanced,
                    'Agile',
                    10,
                    1900,
                    $this->signalShiftConfiguration([
                        [
                            'target_color' => 'coral',
                            'target_shape' => 'diamond',
                            'speed_modifier' => 1.15,
                            'spawn_density' => 5,
                            'wave_count' => 3,
                            'seconds_per_wave' => 3,
                        ],
                        [
                            'target_color' => 'gold',
                            'excluded_shape' => 'square',
                            'size_required' => 'small',
                            'speed_modifier' => 1.25,
                            'spawn_density' => 5,
                            'wave_count' => 3,
                            'seconds_per_wave' => 2,
                        ],
                        [
                            'target_shape' => 'square',
                            'motion_required' => true,
                            'rotation_required' => true,
                            'speed_modifier' => 1.35,
                            'spawn_density' => 6,
                            'wave_count' => 4,
                            'seconds_per_wave' => 2,
                        ],
                    ]),
                ],
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

    /**
     * @param  list<array<string, bool|float|int|string>>  $rounds
     * @return array<string, mixed>
     */
    private function signalShiftConfiguration(array $rounds): array
    {
        return [
            'content_version' => 2,
            'lives' => 3,
            'combo_milestone' => 4,
            'palette' => ['teal', 'gold', 'coral'],
            'shapes' => ['circle', 'square', 'diamond'],
            'rounds' => $rounds,
        ];
    }
}
