<?php

namespace Database\Factories;

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
            'slug' => 'first-step',
            'name' => 'First Step',
            'description' => 'Complete your first daily workout.',
            'type' => AchievementType::FirstWorkout,
            'criterion' => ['workouts' => 1],
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
