<?php

namespace Database\Factories;

use App\Models\Challenge;
use App\Models\Game;
use App\Models\GameLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challenge>
 */
class ChallengeFactory extends Factory
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
            'game_level_id' => function (array $attributes): int {
                return GameLevel::factory()->create([
                    'game_id' => $attributes['game_id'],
                ])->getKey();
            },
            'slug' => 'sample-challenge',
            'mode' => null,
            'content_version' => 1,
            'prompt' => 'Choose the option that matches the prompt.',
            'payload' => ['options' => ['alpha', 'beta', 'gamma', 'delta']],
            'accepted_answers' => [[0]],
            'explanation' => 'The first option is the intended match.',
            'hint' => 'Read the prompt carefully.',
            'is_active' => true,
        ];
    }
}
