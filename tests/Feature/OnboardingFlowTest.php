<?php

use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Onboarding;
use Native\Mobile\Edge\Transition;
use Native\Mobile\Testing\Native;
use Nativephp\NativeUi\Theme;

test('first launch replaces the shell home with onboarding', function () {
    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertReplacedWith('/onboarding')
        ->assertTransition(Transition::Fade);
});

test('an incomplete local profile respects reduced motion when entering onboarding', function () {
    $profile = Profile::factory()->create();
    Setting::factory()->for($profile)->create([
        'reduced_motion' => true,
    ]);

    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertReplacedWith('/onboarding')
        ->assertTransition(Transition::None);
});

test('returning users remain in the existing application shell', function () {
    $profile = Profile::factory()->onboarded()->create();
    Setting::factory()->for($profile)->create();

    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertNoNavigation()
        ->assertSee('TODAY’S SESSION')
        ->assertAccessible();
});

test('returning users cannot restart onboarding accidentally', function () {
    $profile = Profile::factory()->onboarded()->create();
    Setting::factory()->for($profile)->create([
        'reduced_motion' => true,
    ]);

    Native::visit('/onboarding')
        ->assertScreen(Onboarding::class)
        ->assertReplacedWith('/')
        ->assertTransition(Transition::None);
});

test('welcome communicates Ennoble and exposes accessible animated progress', function () {
    Native::visit('/onboarding')
        ->assertSee('Train a sharper mind.')
        ->assertSee('Get started')
        ->assertElement('native_root_stack')
        ->assertMissingElement('native_root_tabs')
        ->assertNavBarHidden()
        ->assertElement('row', fn (array $node): bool => ($node['props']['a11y_label'] ?? null) === 'Onboarding progress, step 1 of 6')
        ->assertElement('column', fn (array $node): bool => array_key_exists('animate-duration', $node['props'] ?? []))
        ->assertAccessible();
});

test('scrolling onboarding steps remain inside chrome-free native layout geometry', function () {
    Native::visit('/onboarding')
        ->set('currentStep', 2)
        ->assertElement('native_root_stack')
        ->assertMissingElement('native_root_tabs')
        ->assertNavBarHidden()
        ->assertAccessible();
});

test('appearance choices apply the explicit palette immediately in onboarding', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/onboarding')
        ->set('currentStep', 5)
        ->selectRadio('themePreference', ThemePreference::Dark->value)
        ->assertSet('themePreference', ThemePreference::Dark->value)
        ->assertNoNavigation()
        ->assertAccessible();

    $tokens = Theme::all();

    expect(data_get($tokens, 'light.background'))
        ->toBe(data_get($tokens, 'dark.background'))
        ->and(data_get($tokens, 'dark.background'))->toBe('#0F0F11');
});

test('the complete onboarding journey persists local choices and enters home', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $screen = Native::visit('/onboarding')
        ->tap('Get started')
        ->assertSet('currentStep', 2)
        ->assertSee('What should we train first?')
        ->selectRadio('trainingGoal', TrainingGoal::ThinkingSpeed->value)
        ->assertSet('trainingGoal', TrainingGoal::ThinkingSpeed->value)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 3)
        ->assertSee('Choose your pace.')
        ->selectRadio('difficulty', Difficulty::Adaptive->value)
        ->assertSet('difficulty', Difficulty::Adaptive->value)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 4)
        ->input('displayName', '  Ada   Local  ')
        ->assertSet('displayName', '  Ada   Local  ')
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 5)
        ->selectRadio('themePreference', ThemePreference::Dark->value)
        ->toggle('soundEnabled', false)
        ->toggle('hapticsEnabled', false)
        ->assertSet('themePreference', ThemePreference::Dark->value)
        ->assertSet('soundEnabled', false)
        ->assertSet('hapticsEnabled', false)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 6)
        ->assertSee('Ready for day one.')
        ->assertSee('Ada Local')
        ->assertSee('Improve Thinking Speed')
        ->assertSee('Adaptive')
        ->assertSee('Dark')
        ->assertAccessible()
        ->tap('Start training')
        ->assertReplacedWith('/')
        ->assertTransition(Transition::Fade);

    $profile = Profile::query()->with('setting')->sole();

    expect($profile->display_name)->toBe('Ada Local')
        ->and($profile->training_goal)->toBe(TrainingGoal::ThinkingSpeed)
        ->and($profile->difficulty_preference)->toBe(Difficulty::Adaptive)
        ->and($profile->onboarding_completed_at)->not->toBeNull()
        ->and($profile->setting)->toBeInstanceOf(Setting::class)
        ->and($profile->setting->theme_preference)->toBe(ThemePreference::Dark)
        ->and($profile->setting->sound_enabled)->toBeFalse()
        ->and($profile->setting->haptics_enabled)->toBeFalse()
        ->and($profile->setting->reduced_motion)->toBeFalse();
});

test('goal and difficulty steps remain disabled until one option is selected', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::test(Onboarding::class)
        ->set('currentStep', 2)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === true)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->selectRadio('trainingGoal', TrainingGoal::Focus->value)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->selectRadio('difficulty', Difficulty::Beginner->value)
        ->call('nextStep')
        ->assertSet('currentStep', 4);
});

test('selection controls expose their individual visible labels to assistive technology', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 2)
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Focus')
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Communication')
        ->assertAccessible()
        ->set('currentStep', 3)
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Gentle')
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Adaptive')
        ->assertAccessible()
        ->set('currentStep', 5)
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Use device setting')
        ->assertElement('radio', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Dark')
        ->assertAccessible();
});

test('the onboarding domain trims display names and enforces the shared limit', function () {
    app(OnboardingService::class)->complete(
        displayName: '   ',
        trainingGoal: TrainingGoal::Balanced,
        difficulty: Difficulty::Intermediate,
        themePreference: ThemePreference::System,
        soundEnabled: true,
        hapticsEnabled: true,
        reducedMotion: false,
    );

    expect(Profile::query()->sole()->display_name)->toBe('')
        ->and(app(OnboardingService::class)->isComplete())->toBeTrue();

    Profile::query()->delete();

    expect(fn () => app(ProfileService::class)->createOrUpdate(str_repeat('A', 41)))
        ->toThrow(InvalidArgumentException::class);
});

test('an empty display name cannot advance beyond the name step', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 4)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === true)
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->input('displayName', '   ')
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->input('displayName', '  Ada  ')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === false)
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->assertAccessible();
});

test('an overlong display name cannot advance and exposes its validation state', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 4)
        ->input('displayName', str_repeat('A', 41))
        ->assertSee('Use 40 characters or fewer.')
        ->assertElement('outlined_text_input', fn (array $node): bool => ($node['props']['is_error'] ?? false) === true)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === true)
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->assertAccessible();
});

test('back navigation stays inside the journey and ready exposes a loading action', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 3)
        ->pressBack()
        ->assertSet('currentStep', 2)
        ->assertNoNavigation()
        ->set('currentStep', 6)
        ->set('trainingGoal', TrainingGoal::Balanced->value)
        ->set('difficulty', Difficulty::Intermediate->value)
        ->set('isSaving', true)
        ->assertElement('column', fn (array $node): bool => ($node['ref'] ?? null) === 'onboarding-actions')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Start training'
            && ($node['props']['loading'] ?? false) === true);
});

test('a persistence failure remains recoverable on the ready step', function () {
    Profile::creating(function (): never {
        throw new RuntimeException('Simulated local persistence failure.');
    });

    Native::test(Onboarding::class)
        ->set('currentStep', 6)
        ->set('trainingGoal', TrainingGoal::Balanced->value)
        ->set('difficulty', Difficulty::Intermediate->value)
        ->set('displayName', 'Ada')
        ->call('completeOnboarding')
        ->assertSee('Your choices could not be saved. Please try again.')
        ->assertSet('isSaving', false)
        ->assertNoNavigation()
        ->assertAccessible();

    expect(Profile::query()->count())->toBe(0);
});

test('reduced motion disables authored onboarding animation durations', function () {
    Native::test(Onboarding::class)
        ->set('reducedMotion', true)
        ->assertElement('column', fn (array $node): bool => array_key_exists('animate-duration', $node['props'] ?? [])
            && ($node['props']['animate-duration'] ?? null) === 0.0)
        ->assertAccessible();
});
