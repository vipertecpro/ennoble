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
            'type' => GameType::SignalShift,
            'slug' => 'signal-shift',
            'name' => 'Signal Shift',
            'description' => 'Respond accurately as visual rules shift.',
            'status' => GameStatus::Playable,
            'sort_order' => 1,
            'skill_keys' => ['focus', 'speed', 'precision', 'adaptability'],
            'configuration' => ['content_version' => 1],
        ];
    }

    public function clearThought(): static
    {
        return $this->state(fn (): array => [
            'type' => GameType::ClearThought,
            'slug' => 'clear-thought',
            'name' => 'Clear Thought',
            'description' => 'Strengthen clear, concise written reasoning.',
            'sort_order' => 2,
            'skill_keys' => ['clarity', 'structure', 'critical_reading'],
        ]);
    }
}
