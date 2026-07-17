<?php

use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\WorkoutPreview;
use App\NativeLayouts\EnnobleLayout;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $profile = LocalProfile::factory()->onboarded()->create();
    Setting::factory()->for($profile)->create();
});

test('all application shell routes are registered with the expected layout', function () {
    expect(NativeRouter::registeredRoutes())->toMatchArray([
        '/splash' => ['class' => Splash::class, 'layout' => null],
        '/onboarding' => ['class' => Onboarding::class, 'layout' => null],
        '/' => ['class' => Home::class, 'layout' => EnnobleLayout::class],
        '/workout' => ['class' => WorkoutPreview::class, 'layout' => EnnobleLayout::class],
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
    'splash' => ['/splash', Splash::class, 'Native application shell ready.'],
    'home' => ['/', Home::class, 'Today’s Workout'],
    'workout' => ['/workout', WorkoutPreview::class, 'Your workout is ready'],
    'games' => ['/games', Games::class, 'Train with purpose.'],
    'progress' => ['/progress', Progress::class, 'Progress shell ready'],
    'profile' => ['/profile', Profile::class, 'Profile shell ready'],
    'settings' => ['/settings', Settings::class, 'Settings shell ready'],
    'about' => ['/about', About::class, 'About shell ready'],
]);

test('splash replaces itself with home', function () {
    Native::visit('/splash')
        ->tap('Enter Ennoble')
        ->assertReplacedWith('/');
});

test('the native tab bar exposes all destinations and tracks the active route', function (
    string $uri,
    string $activeTab,
) {
    Native::visit($uri)
        ->assertHasTabBar()
        ->assertHasTab('Home')
        ->assertHasTab('Games')
        ->assertHasTab('Progress')
        ->assertHasTab('Profile')
        ->assertTabActive($activeTab)
        ->assertTabBarVisible();
})->with([
    'home' => ['/', 'Home'],
    'games' => ['/games', 'Games'],
    'progress' => ['/progress', 'Progress'],
    'profile' => ['/profile', 'Profile'],
]);

test('profile settings and about placeholders form a working native flow', function () {
    Native::visit('/profile')
        ->tap('Open settings')
        ->assertNavigatedTo('/settings')
        ->follow()
        ->assertScreen(Settings::class)
        ->assertNavTitle('Settings')
        ->assertTabBarHidden()
        ->tap('About Ennoble')
        ->assertNavigatedTo('/about')
        ->follow()
        ->assertScreen(About::class)
        ->assertNavTitle('About')
        ->assertTabBarHidden();
});

test('home shared screen container renders full loading and recoverable error states', function () {
    Native::visit('/')
        ->set('dashboardState', 'loading')
        ->assertSee('Loading your Ennoble dashboard')
        ->set('dashboardState', 'error')
        ->assertSee('Your dashboard could not be loaded')
        ->tap('Retry dashboard')
        ->assertSee('Today’s Workout');
});
