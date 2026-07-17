<?php

namespace App\Domain\Settings;

use App\Enums\ThemePreference;
use App\Models\Profile;
use App\Models\Setting;
use Illuminate\Support\Arr;

final class SettingsService
{
    private const ACCESSIBILITY_KEYS = [
        'extended_time',
        'high_contrast',
        'screen_reader_optimized',
    ];

    /**
     * Retrieve settings, creating safe local defaults when needed.
     */
    public function forProfile(Profile $profile): Setting
    {
        return $profile->setting()->firstOrCreate();
    }

    /**
     * Persist all local preferences atomically for a profile.
     *
     * @param  array<string, bool>  $accessibilityPreferences
     */
    public function save(
        Profile $profile,
        ThemePreference $themePreference,
        bool $soundEnabled,
        bool $hapticsEnabled,
        bool $reducedMotion,
        bool $dailyReminderEnabled,
        array $accessibilityPreferences = [],
    ): Setting {
        $preferences = Arr::only($accessibilityPreferences, self::ACCESSIBILITY_KEYS);
        $preferences = array_map(static fn (mixed $value): bool => (bool) $value, $preferences);

        return $profile->setting()->updateOrCreate(
            [],
            [
                'theme_preference' => $themePreference,
                'sound_enabled' => $soundEnabled,
                'haptics_enabled' => $hapticsEnabled,
                'reduced_motion' => $reducedMotion,
                'daily_reminder_enabled' => $dailyReminderEnabled,
                'accessibility_preferences' => $preferences,
            ],
        );
    }
}
