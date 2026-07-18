<?php

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Testing\Native;
use Nativephp\NativeUi\Theme;

afterEach(function () {
    Theme::load(config('native-ui.theme', []));
    TailwindParser::clearCache();
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
        ->and($tokens['dark']['primary'])->toBe($expectedPrimary)
        ->and(Theme::get('color-scheme'))->toBe($preference->value);
})->with([
    'light' => [ThemePreference::Light, '#1B1B1F'],
    'dark' => [ThemePreference::Dark, '#C5DB55'],
]);

test('changing appearance clears parsed semantic colors before the next native frame', function () {
    app(ThemeManager::class)->apply(ThemePreference::Light);

    expect(TailwindParser::parse('bg-theme-background'))
        ->toMatchArray(['bg' => '#F5F5F2']);

    app(ThemeManager::class)->apply(ThemePreference::Dark);

    expect(TailwindParser::parse('bg-theme-background'))
        ->toMatchArray(['bg' => '#0F0F11']);
});

test('system preference preserves distinct light and dark semantic palettes', function () {
    app(ThemeManager::class)->apply(ThemePreference::System);
    $tokens = Theme::all();

    expect($tokens['light']['background'])->toBe('#F5F5F2')
        ->and($tokens['dark']['background'])->toBe('#0F0F11')
        ->and($tokens['light']['background'])->not->toBe($tokens['dark']['background'])
        ->and(Theme::get('color-scheme'))->toBe('system');
});

test('explicit appearance keeps typed icon colors independent from device appearance', function (
    ThemePreference $preference,
    string $expectedColor,
) {
    $profile = Profile::factory()->onboarded()->create();
    Setting::factory()->for($profile)->create([
        'theme_preference' => $preference,
    ]);

    app(ThemeManager::class)->apply($preference);

    Native::visit('/')
        ->assertElement(
            'icon',
            fn (array $node): bool => data_get($node, 'props.color') === $expectedColor
                && data_get($node, 'props.dark_color') === $expectedColor,
        );
})->with([
    'light' => [ThemePreference::Light, '#1B1B1F'],
    'dark' => [ThemePreference::Dark, '#F5F5F4'],
]);

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
    $theme = config('native-ui.theme');

    expect(DesignTokens::SEMANTIC_COLORS)->toHaveCount(19)
        ->and($theme['light'])->toHaveKeys(DesignTokens::SEMANTIC_COLORS)
        ->and($theme['dark'])->toHaveKeys(DesignTokens::SEMANTIC_COLORS)
        ->and(DesignTokens::TYPOGRAPHY)->toHaveKeys([
            'display-xl',
            'display-large',
            'headline',
            'title',
            'section',
            'body',
            'body-small',
            'caption',
            'button',
            'badge',
            'numeric',
        ])
        ->and(DesignTokens::SPACING)->toHaveKeys(['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'])
        ->and(DesignTokens::LAYOUT_SPACING)->toHaveKeys(['screen-margin', 'section', 'card', 'content', 'compact', 'touch'])
        ->and(DesignTokens::CORNER_RADII)->toHaveKeys(['small', 'medium', 'large', 'full'])
        ->and(DesignTokens::ELEVATION)->toHaveKeys(['none', 'low', 'medium', 'high'])
        ->and(DesignTokens::MOTION_DURATION)->toHaveKeys(['fast', 'normal', 'slow', 'spring', 'success', 'error'])
        ->and(DesignTokens::OPACITY)->toHaveKeys(['disabled', 'muted', 'overlay', 'pressed'])
        ->and(DesignTokens::CARD_VARIANTS)->toHaveKeys([
            'hero',
            'workout',
            'game',
            'metric',
            'achievement',
            'coming-soon',
            'standard',
        ])
        ->and(DesignTokens::CARD_CONTENT_VARIANTS)->toHaveKeys([
            'hero',
            'workout',
            'game',
            'metric',
            'achievement',
            'coming-soon',
            'standard',
        ])
        ->and(DesignTokens::CARD_INSET_VARIANTS)->toHaveKeys([
            'hero',
            'workout',
            'game',
            'metric',
            'achievement',
            'coming-soon',
            'standard',
        ])
        ->and(DesignTokens::ICON_SIZE)->toHaveKeys(['small', 'medium', 'large', 'hero'])
        ->and(DesignTokens::SCREEN_PADDING)->toBe(16)
        ->and(DesignTokens::COMPONENT_SPACING)->toBe(16)
        ->and(DesignTokens::MINIMUM_TOUCH_TARGET)->toBeGreaterThanOrEqual(44);
});
