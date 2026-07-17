<?php

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Nativephp\NativeUi\Theme;

afterEach(function () {
    Theme::load(config('native-ui.theme', []));
});

test('theme manager resolves light dark and system appearances', function () {
    $manager = app(ThemeManager::class);

    expect($manager->appearance(ThemePreference::Light, 'dark'))->toBe('light')
        ->and($manager->appearance(ThemePreference::Dark, 'light'))->toBe('dark')
        ->and($manager->appearance(ThemePreference::System, 'dark'))->toBe('dark')
        ->and($manager->appearance(ThemePreference::System, 'unexpected'))->toBe('light');
});

test('explicit appearance preferences apply one semantic palette to both renderer modes', function (
    ThemePreference $preference,
    string $expectedPrimary,
) {
    $manager = app(ThemeManager::class);
    $manager->apply($preference);
    $tokens = Theme::all();

    expect($tokens['light']['primary'])->toBe($expectedPrimary)
        ->and($tokens['dark']['primary'])->toBe($expectedPrimary);
})->with([
    'light' => [ThemePreference::Light, '#5B43D6'],
    'dark' => [ThemePreference::Dark, '#A99AF5'],
]);

test('system preference preserves distinct light and dark semantic palettes', function () {
    app(ThemeManager::class)->apply(ThemePreference::System);
    $tokens = Theme::all();

    expect($tokens['light']['background'])->toBe('#F7F6FB')
        ->and($tokens['dark']['background'])->toBe('#17161D')
        ->and($tokens['light']['background'])->not->toBe($tokens['dark']['background']);
});

test('saved Prompt 2 settings drive theme and reduced motion behavior', function () {
    $profile = app(ProfileService::class)->createOrUpdate(
        displayName: 'Shell Tester',
        trainingGoal: TrainingGoal::Balanced,
        difficulty: Difficulty::Intermediate,
    );

    app(SettingsService::class)->save(
        profile: $profile,
        themePreference: ThemePreference::Dark,
        soundEnabled: true,
        hapticsEnabled: true,
        reducedMotion: true,
        dailyReminderEnabled: false,
    );

    $manager = app(ThemeManager::class);

    expect($manager->currentPreference())->toBe(ThemePreference::Dark)
        ->and($manager->applyCurrent())->toBe(ThemePreference::Dark)
        ->and($manager->prefersReducedMotion())->toBeTrue()
        ->and($manager->motionDuration(MotionToken::Success))->toBe(0);
});

test('design tokens expose the complete reusable foundation', function () {
    expect(DesignTokens::TYPOGRAPHY)->toHaveKeys(['display', 'heading', 'body', 'label', 'numeric'])
        ->and(DesignTokens::SPACING)->toHaveKeys(['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl'])
        ->and(DesignTokens::CORNER_RADII)->toHaveKeys(['small', 'medium', 'large', 'full'])
        ->and(DesignTokens::ELEVATION)->toHaveKeys(['none', 'low', 'medium', 'high'])
        ->and(DesignTokens::MOTION_DURATION)->toHaveKeys(['fast', 'normal', 'slow', 'spring', 'success', 'error'])
        ->and(DesignTokens::OPACITY)->toHaveKeys(['disabled', 'muted', 'overlay', 'pressed'])
        ->and(DesignTokens::ICON_SIZE)->toHaveKeys(['small', 'medium', 'large', 'hero'])
        ->and(DesignTokens::SCREEN_PADDING)->toBe(20)
        ->and(DesignTokens::COMPONENT_SPACING)->toBe(16)
        ->and(DesignTokens::MINIMUM_TOUCH_TARGET)->toBeGreaterThanOrEqual(44);
});
