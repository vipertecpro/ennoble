<?php

use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\AchievementCategory;
use App\NativeComponents\Screens\Achievements;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\MyDetails;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\QuickMathGame;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\WordMatchGame;
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
        '/games' => ['class' => Games::class, 'layout' => EnnobleLayout::class],
        '/games/{slug}' => ['class' => GameDetail::class, 'layout' => EnnobleLayout::class],
        '/play/word-match/{session}' => ['class' => WordMatchGame::class, 'layout' => EnnobleLayout::class],
        '/play/quick-math/{session}' => ['class' => QuickMathGame::class, 'layout' => EnnobleLayout::class],
        '/achievements' => ['class' => Achievements::class, 'layout' => EnnobleLayout::class],
        '/achievements/{category}' => ['class' => AchievementCategory::class, 'layout' => EnnobleLayout::class],
        '/profile' => ['class' => Profile::class, 'layout' => EnnobleLayout::class],
        '/my-details' => ['class' => MyDetails::class, 'layout' => EnnobleLayout::class],
        '/settings' => ['class' => Settings::class, 'layout' => EnnobleLayout::class],
        '/about' => ['class' => About::class, 'layout' => EnnobleLayout::class],
    ]);
});

test('the home screen shows a single recent game card and the streak glance', function () {
    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertSee('Start playing')
        ->assertSee('Word Match')
        ->assertDontSee('Quick Math')
        ->assertSee('Day streak')
        ->assertSee('Games played')
        ->assertDontSee('Best score')
        ->assertDontSee('Latest badge')
        ->assertSee('No achievements yet')
        ->assertAccessible();
});

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

test('primary screens use a bounded scroll viewport and symmetric content gutters', function () {
    Native::visit('/')
        ->assertElement(
            'scroll_view',
            fn (array $node): bool => data_get($node, 'layout.overflow') === 2
                && data_get($node, 'layout.flex_grow') === 1.0,
        )
        ->assertElement(
            'column',
            fn (array $node): bool => data_get($node, 'layout.width') === 'fill'
                && data_get($node, 'layout.padding.1') === 16.0
                && data_get($node, 'layout.padding.3') === 16.0,
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
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Achievements')
        ->assertElement('bottom_nav_item', fn (array $node): bool => data_get($node, 'props.label') === 'Profile')
        ->assertElement(
            'bottom_nav_item',
            fn (array $node): bool => data_get($node, 'props.label') === $activeTab
                && data_get($node, 'props.active') === true,
        );
})->with([
    'home' => ['/', 'Home'],
    'games' => ['/games', 'Games'],
    'achievements' => ['/achievements', 'Achievements'],
    'profile' => ['/profile', 'Profile'],
]);

test('home renders full loading and recoverable error states', function () {
    Native::visit('/')
        ->set('screenState', 'loading')
        ->assertSee('Loading your home screen')
        ->set('screenState', 'error')
        ->assertSee('Your home screen could not be loaded')
        ->tap('Retry')
        ->assertSee('Word Match');
});
