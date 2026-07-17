<?php

namespace Database\Factories;

use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyWorkout>
 */
class DailyWorkoutFactory extends Factory
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
            'workout_date' => today(),
            'status' => WorkoutStatus::Pending,
            'generation_version' => 1,
            'training_seconds' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkoutStatus::Completed,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'training_seconds' => 300,
            'accuracy' => 90,
        ]);
    }
}
