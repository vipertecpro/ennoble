<?php

use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\MyDetails;
use Carbon\CarbonImmutable;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 09:30:00');

    $this->profile = LocalProfile::factory()->onboarded()->create([
        'display_name' => 'Ada',
        'training_goal' => TrainingGoal::Focus,
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'reduced_motion' => false,
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the details editor loads the persisted local identity', function () {
    Native::visit('/my-details')
        ->assertScreen(MyDetails::class)
        ->assertSet('displayName', 'Ada')
        ->assertSet('trainingGoal', TrainingGoal::Focus->value)
        ->assertSet('difficulty', Difficulty::Intermediate->value)
        ->assertSee('Training focus')
        ->assertSee('Training pace')
        ->assertAccessible();
});

test('edited details persist through the existing profile service', function () {
    Native::visit('/my-details')
        ->set('displayName', 'Grace')
        ->assertSee('Save changes')
        ->set('trainingGoal', TrainingGoal::MentalSharpness->value)
        ->set('difficulty', Difficulty::Advanced->value)
        ->tap('Save changes')
        ->assertSet('savedDisplayName', 'Grace')
        ->assertDontSee('Save changes')
        ->assertAccessible();

    $profile = $this->profile->refresh();

    expect($profile->display_name)->toBe('Grace')
        ->and($profile->training_goal)->toBe(TrainingGoal::MentalSharpness)
        ->and($profile->difficulty_preference)->toBe(Difficulty::Advanced);
});

test('an overlong display name blocks saving with honest supporting copy', function () {
    Native::visit('/my-details')
        ->set('displayName', str_repeat('a', 41))
        ->assertSee('Use 40 characters or fewer.')
        ->tap('Save changes');

    expect($this->profile->refresh()->display_name)->toBe('Ada');
});

test('forged detail selections revert to the persisted values', function () {
    Native::visit('/my-details')
        ->set('trainingGoal', 'not-a-goal')
        ->assertSet('trainingGoal', TrainingGoal::Focus->value)
        ->set('difficulty', 'impossible')
        ->assertSet('difficulty', Difficulty::Intermediate->value);
});

test('an incomplete profile is returned to onboarding before details load', function () {
    $this->profile->update(['onboarding_completed_at' => null]);

    Native::visit('/my-details')
        ->assertReplacedWith('/onboarding');
});
