<?php

use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\WorkoutComplete;
use App\NativeComponents\Screens\WorkoutGameContainer;
use App\NativeComponents\Screens\WorkoutIntroduction;
use App\NativeComponents\Screens\WorkoutPreparation;
use App\NativeComponents\Screens\WorkoutTransition;
use App\NativeLayouts\EnnobleLayout;
use Illuminate\Support\Facades\Route;

Route::native('/splash', Splash::class)->name('native.splash');
Route::native('/onboarding', Onboarding::class)->name('native.onboarding');

Route::nativeGroup(EnnobleLayout::class, function (): void {
    Route::native('/', Home::class)->name('native.home');
    Route::native('/workout', WorkoutIntroduction::class)->name('native.workout');
    Route::native('/workout/preparation/{session}', WorkoutPreparation::class)
        ->name('native.workout.preparation');
    Route::native('/workout/game/{session}', WorkoutGameContainer::class)
        ->name('native.workout.game');
    Route::native('/workout/transition/{item}', WorkoutTransition::class)
        ->name('native.workout.transition');
    Route::native('/workout/complete/{workout}', WorkoutComplete::class)
        ->name('native.workout.complete');
    Route::native('/games', Games::class)->name('native.games');
    Route::native('/progress', Progress::class)->name('native.progress');
    Route::native('/profile', Profile::class)->name('native.profile');
    Route::native('/settings', Settings::class)->name('native.settings');
    Route::native('/about', About::class)->name('native.about');
});
