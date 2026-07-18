<?php

use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\ClearThoughtGame;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\SignalShiftGame;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\WorkoutComplete;
use App\NativeComponents\Screens\WorkoutIntroduction;
use App\NativeComponents\Screens\WorkoutPreparation;
use App\NativeComponents\Screens\WorkoutTransition;
use App\NativeLayouts\EnnobleLayout;
use App\NativeLayouts\OnboardingLayout;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $profile = LocalProfile::factory()->onboarded()->create();
    Setting::factory()->for($profile)->create();
});

test('all application shell routes are registered with the expected layout', function () {
    expect(NativeRouter::registeredRoutes())->toMatchArray([
        '/splash' => ['class' => Splash::class, 'layout' => null],
        '/onboarding' => ['class' => Onboarding::class, 'layout' => OnboardingLayout::class],
        '/' => ['class' => Home::class, 'layout' => EnnobleLayout::class],
        '/workout' => ['class' => WorkoutIntroduction::class, 'layout' => EnnobleLayout::class],
        '/workout/preparation/{session}' => ['class' => WorkoutPreparation::class, 'layout' => EnnobleLayout::class],
        '/workout/game/signal-shift/{session}' => ['class' => SignalShiftGame::class, 'layout' => EnnobleLayout::class],
        '/workout/game/clear-thought/{session}' => ['class' => ClearThoughtGame::class, 'layout' => EnnobleLayout::class],
        '/workout/transition/{item}' => ['class' => WorkoutTransition::class, 'layout' => EnnobleLayout::class],
        '/workout/complete/{workout}' => ['class' => WorkoutComplete::class, 'layout' => EnnobleLayout::class],
        '/games' => ['class' => Games::class, 'layout' => EnnobleLayout::class],
        '/progress' => ['class' => Progress::class, 'layout' => EnnobleLayout::class],
        '/profile' => ['class' => Profile::class, 'layout' => EnnobleLayout::class],
        '/settings' => ['class' => Settings::class, 'layout' => EnnobleLayout::class],
        '/about' => ['class' => About::class, 'layout' => EnnobleLayout::class],
    ]);
});

test('application screens render and pass the in-process accessibility audit', function (
    string $uri,
    string $component,
    string $visibleText,
) {
    Native::visit($uri)
        ->assertScreen($component)
        ->assertSee($visibleText)
        ->assertAccessible();
})->with([
    'splash' => ['/splash', Splash::class, 'A private daily practice for a clearer mind.'],
    'home' => ['/', Home::class, 'TODAY’S PRACTICE'],
    'workout' => ['/workout', WorkoutIntroduction::class, 'Ready your mind.'],
    'games' => ['/games', Games::class, 'Train with purpose.'],
    'progress' => ['/progress', Progress::class, 'Progress you can trust.'],
    'profile' => ['/profile', Profile::class, 'Your details'],
    'settings' => ['/settings', Settings::class, 'Every preference is stored only on this device.'],
    'about' => ['/about', About::class, 'A private daily practice for a clearer mind.'],
]);

test('splash replaces itself with home', function () {
    Native::visit('/splash')
        ->tap('Enter Ennoble')
        ->assertReplacedWith('/');
});

test('onboarding uses native layout geometry without exposing application chrome', function () {
    Native::visit('/onboarding')
        ->assertElement('native_root_stack')
        ->assertMissingElement('native_root_tabs')
        ->assertNavBarHidden()
        ->assertAccessible();
});

test('primary screens use a bounded scroll viewport and content width', function () {
    Native::visit('/')
        ->assertElement(
            'scroll_view',
            fn (array $node): bool => data_get($node, 'layout.overflow') === 2
                && data_get($node, 'layout.flex_grow') === 1.0,
        )
        ->assertElement(
            'column',
            fn (array $node): bool => data_get($node, 'layout.width') === 320.0,
        );
});

test('the native tab bar exposes all destinations and tracks the active route', function (
    string $uri,
    string $activeTab,
) {
    Native::visit($uri)
        ->assertElement('bottom_nav')
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Home')
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Games')
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Progress')
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Profile')
        ->assertElement(
            'bottom_nav_item',
            fn (array $node): bool => data_get($node, 'props.label') === $activeTab
                && data_get($node, 'props.active') === true,
        );
})->with([
    'home' => ['/', 'Home'],
    'games' => ['/games', 'Games'],
    'progress' => ['/progress', 'Progress'],
    'profile' => ['/profile', 'Profile'],
]);

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

test('home shared screen container renders full loading and recoverable error states', function () {
    Native::visit('/')
        ->set('dashboardState', 'loading')
        ->assertSee('Loading your Ennoble dashboard')
        ->set('dashboardState', 'error')
        ->assertSee('Your dashboard could not be loaded')
        ->tap('Retry dashboard')
        ->assertSee('TODAY’S PRACTICE');
});
