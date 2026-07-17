<?php

namespace Database\Factories;

use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\DailyWorkoutItem;
use App\Models\Game;
use App\Models\GameLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyWorkoutItem>
 */
class DailyWorkoutItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_workout_id' => DailyWorkout::factory(),
            'game_id' => Game::factory(),
            'game_level_id' => function (array $attributes): int {
                return GameLevel::factory()->create([
                    'game_id' => $attributes['game_id'],
                ])->getKey();
            },
            'position' => 1,
            'status' => WorkoutStatus::Pending,
            'configuration' => ['round_count' => 8, 'content_version' => 1],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkoutStatus::Completed,
            'started_at' => now()->subMinutes(3),
            'completed_at' => now(),
        ]);
    }
}
