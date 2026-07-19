<?php

use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\ClearThoughtGame;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeComponents\Screens\QuickMathGame;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\SignalShiftGame;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\WordMatchGame;
use App\NativeComponents\Screens\WorkoutComplete;
use App\NativeComponents\Screens\WorkoutIntroduction;
use App\NativeComponents\Screens\WorkoutPreparation;
use App\NativeComponents\Screens\WorkoutTransition;
use App\NativeLayouts\EnnobleLayout;
use App\NativeLayouts\OnboardingLayout;
use Illuminate\Support\Facades\Route;

Route::native('/splash', Splash::class)->name('native.splash');

Route::nativeGroup(OnboardingLayout::class, function (): void {
    Route::native('/onboarding', Onboarding::class)->name('native.onboarding');
});

Route::nativeGroup(EnnobleLayout::class, function (): void {
    Route::native('/', Home::class)->name('native.home');
    Route::native('/workout', WorkoutIntroduction::class)->name('native.workout');
    Route::native('/workout/preparation/{session}', WorkoutPreparation::class)
        ->name('native.workout.preparation');
    Route::native('/workout/game/signal-shift/{session}', SignalShiftGame::class)
        ->name('native.workout.signal-shift');
    Route::native('/workout/game/clear-thought/{session}', ClearThoughtGame::class)
        ->name('native.workout.clear-thought');
    Route::native('/workout/transition/{item}', WorkoutTransition::class)
        ->name('native.workout.transition');
    Route::native('/workout/complete/{workout}', WorkoutComplete::class)
        ->name('native.workout.complete');
    Route::native('/games', Games::class)->name('native.games');
    Route::native('/games/{slug}', GameDetail::class)->name('native.game.detail');
    Route::native('/play/word-match/{session}', WordMatchGame::class)->name('native.play.word-match');
    Route::native('/play/quick-math/{session}', QuickMathGame::class)->name('native.play.quick-math');
    Route::native('/progress', Progress::class)->name('native.progress');
    Route::native('/profile', Profile::class)->name('native.profile');
    Route::native('/settings', Settings::class)->name('native.settings');
    Route::native('/about', About::class)->name('native.about');
});
