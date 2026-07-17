<?php

namespace Database\Factories;

use App\Enums\ClearThoughtMode;
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
            'game_id' => Game::factory()->clearThought(),
            'game_level_id' => function (array $attributes): int {
                return GameLevel::factory()->create([
                    'game_id' => $attributes['game_id'],
                ])->getKey();
            },
            'slug' => 'remove-filler-words',
            'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
            'content_version' => 1,
            'prompt' => 'Remove the words that do not change the meaning.',
            'payload' => ['tokens' => ['The', 'result', 'was', 'actually', 'clear']],
            'accepted_answers' => [[3]],
            'explanation' => 'Actually adds no useful meaning in this sentence.',
            'hint' => 'Look for a word that adds emphasis but no information.',
            'is_active' => true,
        ];
    }
}
