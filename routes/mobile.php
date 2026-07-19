<?php

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
use Illuminate\Support\Facades\Route;

Route::native('/splash', Splash::class)->name('native.splash');

Route::nativeGroup(OnboardingLayout::class, function (): void {
    Route::native('/onboarding', Onboarding::class)->name('native.onboarding');
});

Route::nativeGroup(EnnobleLayout::class, function (): void {
    Route::native('/', Home::class)->name('native.home');
    Route::native('/games', Games::class)->name('native.games');
    Route::native('/games/{slug}', GameDetail::class)->name('native.game.detail');
    Route::native('/play/word-match/{session}', WordMatchGame::class)->name('native.play.word-match');
    Route::native('/play/quick-math/{session}', QuickMathGame::class)->name('native.play.quick-math');
    Route::native('/achievements', Achievements::class)->name('native.achievements');
    Route::native('/achievements/{category}', AchievementCategory::class)->name('native.achievements.category');
    Route::native('/profile', Profile::class)->name('native.profile');
    Route::native('/my-details', MyDetails::class)->name('native.my-details');
    Route::native('/settings', Settings::class)->name('native.settings');
    Route::native('/about', About::class)->name('native.about');
});
