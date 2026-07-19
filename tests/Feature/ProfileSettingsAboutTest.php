<?php

use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Settings;
use Carbon\CarbonImmutable;
use Native\Mobile\Testing\Native;
use Nativephp\NativeUi\Theme;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 09:30:00');

    $this->profile = LocalProfile::factory()->onboarded()->create([
        'display_name' => 'Ada',
        'training_goal' => TrainingGoal::Focus,
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'theme_preference' => ThemePreference::System,
        'sound_enabled' => true,
        'haptics_enabled' => true,
        'reduced_motion' => false,
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the profile screen renders the local identity and navigation list only', function () {
    Native::visit('/profile')
        ->assertScreen(Profile::class)
        ->assertSee('Ada')
        ->assertSee('Playing since')
        ->assertSee('Improve Focus')
        ->assertSee('Intermediate')
        ->assertSee('My Details')
        ->assertSee('Settings')
        ->assertSee('About Ennoble')
        ->assertDontSee('Your practice')
        ->assertDontSee('Workouts')
        ->assertDontSee('Save changes')
        ->assertAccessible();
});

test('an empty display name renders the friendly identity fallback', function () {
    $this->profile->update(['display_name' => '   ']);

    Native::visit('/profile')
        ->assertSee('Friend')
        ->assertSet('monogram', '')
        ->assertAccessible();
});

test('an incomplete profile is returned to onboarding before the profile loads', function () {
    $this->profile->update(['onboarding_completed_at' => null]);

    Native::visit('/profile')
        ->assertReplacedWith('/onboarding');
});

test('the profile navigation list routes to details, settings, and about', function () {
    Native::visit('/profile')
        ->tap('My Details')
        ->assertNavigatedTo('/my-details');

    Native::visit('/profile')
        ->tap('Settings')
        ->assertNavigatedTo('/settings');

    Native::visit('/profile')
        ->tap('About Ennoble')
        ->assertNavigatedTo('/about');
});

test('profile settings and about form a working native flow', function () {
    Native::visit('/profile')
        ->tap('Settings')
        ->assertNavigatedTo('/settings')
        ->follow()
        ->assertScreen(Settings::class)
        ->assertNavTitle('Settings')
        ->assertMissingElement('bottom_nav')
        ->tap('About Ennoble')
        ->assertNavigatedTo('/about')
        ->follow()
        ->assertScreen(About::class)
        ->assertNavTitle('About')
        ->assertMissingElement('bottom_nav');
});

test('settings render every persisted preference control', function () {
    Native::visit('/settings')
        ->assertScreen(Settings::class)
        ->assertSee('Appearance')
        ->assertSee('Use device setting')
        ->assertSee('Light')
        ->assertSee('Dark')
        ->assertSee('Sound')
        ->assertSee('Haptics')
        ->assertSee('Reduce motion')
        ->assertSee('About Ennoble')
        ->assertSee('Every preference is stored only on this device.')
        ->assertSet('themePreference', ThemePreference::System->value)
        ->assertSet('soundEnabled', true)
        ->assertSet('hapticsEnabled', true)
        ->assertSet('reducedMotion', false)
        ->assertAccessible();
});

test('changing appearance persists and applies the explicit palette immediately', function () {
    Native::visit('/settings')
        ->set('themePreference', ThemePreference::Dark->value)
        ->assertSet('themePreference', ThemePreference::Dark->value);

    $tokens = Theme::all();

    expect($this->profile->refresh()->setting->theme_preference)->toBe(ThemePreference::Dark)
        ->and(data_get($tokens, 'light.background'))
        ->toBe(data_get($tokens, 'dark.background'));
});

test('a forged appearance value falls back to the device setting', function () {
    Native::visit('/settings')
        ->set('themePreference', 'sepia')
        ->assertSet('themePreference', ThemePreference::System->value);

    expect($this->profile->refresh()->setting->theme_preference)->toBe(ThemePreference::System);
});

test('feedback and motion toggles persist atomically', function () {
    Native::visit('/settings')
        ->set('soundEnabled', false)
        ->set('hapticsEnabled', false)
        ->set('reducedMotion', true)
        ->assertSet('motionDuration', 0);

    $setting = $this->profile->refresh()->setting;

    expect($setting->sound_enabled)->toBeFalse()
        ->and($setting->haptics_enabled)->toBeFalse()
        ->and($setting->reduced_motion)->toBeTrue();
});

test('saving a preference preserves untouched reminder and accessibility values', function () {
    Setting::query()->whereBelongsTo($this->profile)->update([
        'daily_reminder_enabled' => true,
        'accessibility_preferences' => ['extended_time' => true],
    ]);

    Native::visit('/settings')
        ->set('soundEnabled', false);

    $setting = $this->profile->refresh()->setting;

    expect($setting->daily_reminder_enabled)->toBeTrue()
        ->and($setting->accessibility_preferences)->toBe(['extended_time' => true]);
});

test('about presents the offline private evidence-first identity', function () {
    Native::visit('/about')
        ->assertScreen(About::class)
        ->assertSee('Ennoble')
        ->assertSee('A private daily practice for a clearer mind.')
        ->assertSee('Offline by design')
        ->assertSee('Private by default')
        ->assertSee('Evidence over estimates')
        ->assertSee('Crafted for quiet, focused minds.')
        ->assertAccessible();
});
