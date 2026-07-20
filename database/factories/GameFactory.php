<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => GameType::WordMatch,
            'slug' => 'word-match',
            'name' => 'Word Match',
            'description' => 'Match each word to its meaning before the timer runs out.',
            'status' => GameStatus::Playable,
            'sort_order' => 1,
            'skill_keys' => ['clarity', 'critical_reading'],
            'configuration' => ['content_version' => 1],
        ];
    }

    public function quickMath(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::QuickMath,
            'slug' => 'quick-math',
            'name' => 'Quick Math',
            'description' => 'Solve fast-fire arithmetic and keep your streak alive.',
            'sort_order' => 2,
            'skill_keys' => ['speed', 'precision', 'focus'],
        ]);
    }

    public function recall(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::Recall,
            'slug' => 'recall',
            'name' => 'Recall',
            'description' => 'Watch the sequence light up, then tap it back from memory.',
            'sort_order' => 3,
            'skill_keys' => ['focus', 'structure', 'adaptability'],
        ]);
    }
}
