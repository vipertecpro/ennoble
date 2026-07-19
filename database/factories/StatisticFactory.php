<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Statistic>
 */
class StatisticFactory extends Factory
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
            'game_id' => null,
            'scope_key' => 'overall',
            'sessions_completed' => 0,
            'correct_count' => 0,
            'attempted_count' => 0,
            'total_response_ms' => 0,
            'response_count' => 0,
            'accuracy' => null,
            'average_response_ms' => null,
            'best_score' => null,
            'longest_combo' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
        ];
    }
}
