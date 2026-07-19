<?php

namespace Database\Factories;

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => null,
            'slug' => fn (): string => 'streak-bronze-'.$this->faker->unique()->numberBetween(1, 100000),
            'name' => 'Streak Starter',
            'description' => 'Play on 2 consecutive days.',
            'type' => AchievementType::Streak,
            'tier' => AchievementTier::Bronze,
            'criterion' => ['threshold' => 2],
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    public function tier(AchievementTier $tier): static
    {
        return $this->state(fn (): array => ['tier' => $tier]);
    }

    public function type(AchievementType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function threshold(int $threshold): static
    {
        return $this->state(fn (): array => ['criterion' => ['threshold' => $threshold]]);
    }
}
