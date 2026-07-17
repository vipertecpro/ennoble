<?php

namespace App\Domain\Onboarding;

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

final class OnboardingService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Determine whether the single local profile finished onboarding.
     */
    public function isComplete(): bool
    {
        return $this->profiles->current()?->onboarding_completed_at !== null;
    }

    /**
     * Persist the complete local onboarding selection as one transaction.
     */
    public function complete(
        ?string $displayName,
        TrainingGoal $trainingGoal,
        Difficulty $difficulty,
        ThemePreference $themePreference,
        bool $soundEnabled,
        bool $hapticsEnabled,
        bool $reducedMotion,
    ): Profile {
        return DB::transaction(function () use (
            $displayName,
            $trainingGoal,
            $difficulty,
            $themePreference,
            $soundEnabled,
            $hapticsEnabled,
            $reducedMotion,
        ): Profile {
            $profile = $this->profiles->createOrUpdate(
                displayName: $displayName,
                trainingGoal: $trainingGoal,
                difficulty: $difficulty,
            );

            $this->settings->save(
                profile: $profile,
                themePreference: $themePreference,
                soundEnabled: $soundEnabled,
                hapticsEnabled: $hapticsEnabled,
                reducedMotion: $reducedMotion,
                dailyReminderEnabled: false,
            );

            $profile->forceFill(['onboarding_completed_at' => now()])->save();

            return $profile->refresh()->load('setting');
        });
    }
}
