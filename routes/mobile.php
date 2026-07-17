<?php

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
use Illuminate\Support\Facades\Route;

Route::native('/splash', Splash::class)->name('native.splash');
Route::native('/onboarding', Onboarding::class)->name('native.onboarding');

Route::nativeGroup(EnnobleLayout::class, function (): void {
    Route::native('/', Home::class)->name('native.home');
    Route::native('/workout', WorkoutPreview::class)->name('native.workout');
    Route::native('/games', Games::class)->name('native.games');
    Route::native('/progress', Progress::class)->name('native.progress');
    Route::native('/profile', Profile::class)->name('native.profile');
    Route::native('/settings', Settings::class)->name('native.settings');
    Route::native('/about', About::class)->name('native.about');
});
