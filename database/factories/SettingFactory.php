<?php

namespace Database\Factories;

use App\Enums\ThemePreference;
use App\Models\Profile;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
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
            'theme_preference' => ThemePreference::System,
            'sound_enabled' => true,
            'haptics_enabled' => true,
            'reduced_motion' => false,
            'daily_reminder_enabled' => false,
            'accessibility_preferences' => [],
        ];
    }
}
