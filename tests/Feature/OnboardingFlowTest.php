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
        ->assertSee('Today’s Workout')
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
    Native::test(Onboarding::class)
        ->assertSee('A clearer mind, one day at a time.')
        ->assertSee('Begin')
        ->assertElement('progress_bar', fn (array $node): bool => ($node['props']['value'] ?? null) === 0.125
            && ($node['props']['a11y_label'] ?? null) === 'Onboarding progress, step 1 of 8')
        ->assertElement('column', fn (array $node): bool => ($node['props']['animate-loop'] ?? false) === true)
        ->assertAccessible();
});

test('the complete onboarding journey persists local choices and enters home', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $screen = Native::visit('/onboarding')
        ->tap('Begin')
        ->assertSet('currentStep', 2)
        ->assertSee('Why Ennoble?')
        ->assertSee('Processing Speed')
        ->assertElement('carousel', fn (array $node): bool => ($node['props']['a11y_label'] ?? null) === 'Why Ennoble training areas')
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 3)
        ->assertSee('Everything stays on this device')
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 4)
        ->selectRadio('trainingGoal', TrainingGoal::ThinkingSpeed->value)
        ->assertSet('trainingGoal', TrainingGoal::ThinkingSpeed->value)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 5)
        ->selectRadio('difficulty', Difficulty::Adaptive->value)
        ->assertSet('difficulty', Difficulty::Adaptive->value)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 6)
        ->input('displayName', '  Ada   Local  ')
        ->assertSet('displayName', '  Ada   Local  ')
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 7)
        ->selectRadio('themePreference', ThemePreference::Dark->value)
        ->toggle('soundEnabled', false)
        ->toggle('hapticsEnabled', false)
        ->toggle('reducedMotion', true)
        ->assertSet('themePreference', ThemePreference::Dark->value)
        ->assertSet('soundEnabled', false)
        ->assertSet('hapticsEnabled', false)
        ->assertSet('reducedMotion', true)
        ->assertAccessible()
        ->tap('Continue')
        ->assertSet('currentStep', 8)
        ->assertSee('Your training space is ready.')
        ->assertSee('Improve Thinking Speed')
        ->assertSee('Adaptive')
        ->assertSee('Dark')
        ->assertSee('Ada Local')
        ->assertSee('5–10 minutes')
        ->assertAccessible()
        ->tap('Start Training')
        ->assertReplacedWith('/')
        ->assertTransition(Transition::None);

    $profile = Profile::query()->with('setting')->sole();

    expect($profile->display_name)->toBe('Ada Local')
        ->and($profile->training_goal)->toBe(TrainingGoal::ThinkingSpeed)
        ->and($profile->difficulty_preference)->toBe(Difficulty::Adaptive)
        ->and($profile->onboarding_completed_at)->not->toBeNull()
        ->and($profile->setting)->toBeInstanceOf(Setting::class)
        ->and($profile->setting->theme_preference)->toBe(ThemePreference::Dark)
        ->and($profile->setting->sound_enabled)->toBeFalse()
        ->and($profile->setting->haptics_enabled)->toBeFalse()
        ->and($profile->setting->reduced_motion)->toBeTrue();
});

test('goal and difficulty steps remain disabled until one option is selected', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::test(Onboarding::class)
        ->set('currentStep', 4)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === true)
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->selectRadio('trainingGoal', TrainingGoal::Focus->value)
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->selectRadio('difficulty', Difficulty::Beginner->value)
        ->call('nextStep')
        ->assertSet('currentStep', 6);
});

test('display name is optional trimmed and bounded by the shared domain limit', function () {
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

test('an overlong display name cannot advance and exposes its validation state', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 6)
        ->input('displayName', str_repeat('A', 41))
        ->assertSee('Use 40 characters or fewer.')
        ->assertElement('outlined_text_input', fn (array $node): bool => ($node['props']['is_error'] ?? false) === true)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Continue'
            && ($node['props']['disabled'] ?? false) === true)
        ->call('nextStep')
        ->assertSet('currentStep', 6)
        ->assertAccessible();
});

test('back navigation stays inside the journey and ready exposes a loading action', function () {
    Native::test(Onboarding::class)
        ->set('currentStep', 3)
        ->pressBack()
        ->assertSet('currentStep', 2)
        ->assertNoNavigation()
        ->set('currentStep', 8)
        ->set('trainingGoal', TrainingGoal::Balanced->value)
        ->set('difficulty', Difficulty::Intermediate->value)
        ->set('isSaving', true)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Start Training'
            && ($node['props']['loading'] ?? false) === true);
});

test('a persistence failure remains recoverable on the ready step', function () {
    Profile::creating(function (): never {
        throw new RuntimeException('Simulated local persistence failure.');
    });

    Native::test(Onboarding::class)
        ->set('currentStep', 8)
        ->set('trainingGoal', TrainingGoal::Balanced->value)
        ->set('difficulty', Difficulty::Intermediate->value)
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
