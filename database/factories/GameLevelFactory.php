<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Game;
use App\Models\GameLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameLevel>
 */
class GameLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'difficulty' => Difficulty::Intermediate,
            'name' => 'Steady',
            'round_count' => 8,
            'target_response_ms' => 2500,
            'configuration' => ['content_version' => 1],
            'is_active' => true,
        ];
    }
}
