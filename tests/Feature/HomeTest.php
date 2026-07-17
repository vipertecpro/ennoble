<?php

use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\Splash;
use App\NativeLayouts\EnnobleLayout;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Testing\Native;

test('all application shell routes are registered with the expected layout', function () {
    expect(NativeRouter::registeredRoutes())->toMatchArray([
        '/splash' => ['class' => Splash::class, 'layout' => null],
        '/' => ['class' => Home::class, 'layout' => EnnobleLayout::class],
        '/games' => ['class' => Games::class, 'layout' => EnnobleLayout::class],
        '/progress' => ['class' => Progress::class, 'layout' => EnnobleLayout::class],
        '/profile' => ['class' => Profile::class, 'layout' => EnnobleLayout::class],
        '/settings' => ['class' => Settings::class, 'layout' => EnnobleLayout::class],
        '/about' => ['class' => About::class, 'layout' => EnnobleLayout::class],
    ]);
});

test('placeholder screens render and pass the in-process accessibility audit', function (
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
    'home' => ['/', Home::class, 'Home shell ready'],
    'games' => ['/games', Games::class, 'Games shell ready'],
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

test('shared screen container renders loading error retry and overlay states', function () {
    Native::visit('/')
        ->call('showLoading')
        ->assertSee('Loading Home')
        ->call('showError', 'Local shell failure')
        ->assertSee('Local shell failure')
        ->tap('Retry')
        ->assertSee('Home shell ready')
        ->call('showDialog')
        ->assertElement('modal', fn (array $node): bool => ($node['props']['visible'] ?? false) === true)
        ->call('dismissDialog')
        ->assertElement('modal', fn (array $node): bool => ($node['props']['visible'] ?? true) === false)
        ->call('showBottomSheet')
        ->assertElement('bottom_sheet', fn (array $node): bool => ($node['props']['visible'] ?? false) === true);
});
