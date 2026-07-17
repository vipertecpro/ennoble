<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'singleton_key' => 'local',
            'display_name' => 'Local Player',
            'training_goal' => TrainingGoal::Balanced,
            'difficulty_preference' => Difficulty::Intermediate,
        ];
    }

    /**
     * Mark the local profile as having completed onboarding.
     */
    public function onboarded(): static
    {
        return $this->state(fn (): array => [
            'onboarding_completed_at' => now(),
        ]);
    }
}
