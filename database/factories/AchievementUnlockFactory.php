<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementUnlock>
 */
class AchievementUnlockFactory extends Factory
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
            'achievement_id' => Achievement::factory(),
            'game_session_id' => null,
            'unlocked_at' => now(),
            'evidence' => ['source' => 'test'],
        ];
    }
}
