<?php

namespace Database\Factories;

use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRound>
 */
class GameRoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'challenge_id' => null,
            'round_number' => 1,
            'outcome' => RoundOutcome::Correct,
            'response_ms' => 1200,
            'score_delta' => 150,
            'combo' => 1,
            'hint_used' => false,
            'response' => ['target' => 'triangle'],
        ];
    }
}
