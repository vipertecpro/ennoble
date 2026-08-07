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

    public function flow(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::Flow,
            'slug' => 'flow',
            'name' => 'Flow',
            'description' => 'Ride the current — swipe with each surge of light before it reaches you.',
            'sort_order' => 4,
            'skill_keys' => ['speed', 'focus', 'adaptability'],
        ]);
    }

    public function leap(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::Leap,
            'slug' => 'leap',
            'name' => 'Leap',
            'description' => 'Obstacles keep coming — tap to jump, and keep your timing as the pace climbs.',
            'sort_order' => 6,
            'skill_keys' => ['speed', 'focus', 'adaptability'],
        ]);
    }

    public function signal(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::Signal,
            'slug' => 'signal',
            'name' => 'Signal',
            'description' => 'Trust the ink, not the word — then watch the rule flip.',
            'sort_order' => 5,
            'skill_keys' => ['focus', 'precision', 'adaptability'],
        ]);
    }
}
