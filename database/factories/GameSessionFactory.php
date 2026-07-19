<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'game_id' => Game::factory(),
            'game_level_id' => function (array $attributes): int {
                return GameLevel::factory()->create([
                    'game_id' => $attributes['game_id'],
                ])->getKey();
            },
            'status' => SessionStatus::InProgress,
            'snapshot_version' => 1,
            'current_round' => 0,
            'state_snapshot' => ['version' => 1],
            'started_at' => now(),
            'last_interaction_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => SessionStatus::Completed,
            'score' => 800,
            'accuracy' => 90,
            'average_response_ms' => 1200,
            'correct_count' => 9,
            'incorrect_count' => 1,
            'best_combo' => 6,
            'completed_at' => now(),
        ]);
    }
}
