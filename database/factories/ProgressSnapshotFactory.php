<?php

namespace Database\Factories;

use App\Enums\SkillKey;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressSnapshot>
 */
class ProgressSnapshotFactory extends Factory
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
            'game_session_id' => null,
            'skill_key' => SkillKey::Focus,
            'score_before' => 500,
            'score_after' => 505,
            'delta' => 5,
            'evidence_count' => 1,
            'recorded_at' => now(),
        ];
    }
}
