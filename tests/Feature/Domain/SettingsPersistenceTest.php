<?php

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use App\Models\Setting;

test('profile service maintains one local profile with default settings', function () {
    $service = app(ProfileService::class);

    $profile = $service->createOrUpdate(
        displayName: '  Ada   Local  ',
        trainingGoal: TrainingGoal::Focus,
        difficulty: Difficulty::Advanced,
    );
    $updated = $service->createOrUpdate(
        displayName: 'Ada Refined',
        trainingGoal: TrainingGoal::Language,
        difficulty: Difficulty::Intermediate,
    );

    expect($profile->is($updated))->toBeTrue()
        ->and($updated->display_name)->toBe('Ada Refined')
        ->and($updated->training_goal)->toBe(TrainingGoal::Language)
        ->and(Profile::query()->count())->toBe(1)
        ->and(Setting::query()->count())->toBe(1)
        ->and($updated->setting->theme_preference)->toBe(ThemePreference::System);
});

test('local profile names are optional and bounded', function () {
    $profile = app(ProfileService::class)->createOrUpdate('   ');

    expect($profile->display_name)->toBe('')
        ->and(fn () => app(ProfileService::class)->createOrUpdate(str_repeat('A', 41)))
        ->toThrow(InvalidArgumentException::class);
});

test('settings persist theme feedback reminder and bounded accessibility preferences', function () {
    $profile = Profile::factory()->create();
    $settings = app(SettingsService::class)->save(
        profile: $profile,
        themePreference: ThemePreference::Dark,
        soundEnabled: false,
        hapticsEnabled: false,
        reducedMotion: true,
        dailyReminderEnabled: true,
        accessibilityPreferences: [
            'extended_time' => true,
            'high_contrast' => 1,
            'unsupported_key' => true,
        ],
    );

    expect($settings->refresh()->theme_preference)->toBe(ThemePreference::Dark)
        ->and($settings->sound_enabled)->toBeFalse()
        ->and($settings->haptics_enabled)->toBeFalse()
        ->and($settings->reduced_motion)->toBeTrue()
        ->and($settings->daily_reminder_enabled)->toBeTrue()
        ->and($settings->accessibility_preferences)->toBe([
            'extended_time' => true,
            'high_contrast' => true,
        ]);
});
