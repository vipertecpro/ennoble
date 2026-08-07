<?php

use App\NativeComponents\Screens\About;
use App\NativeComponents\Screens\AchievementCategory;
use App\NativeComponents\Screens\Achievements;
use App\NativeComponents\Screens\AxisGame;
use App\NativeComponents\Screens\FlowGame;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\MyDetails;
use App\NativeComponents\Screens\Onboarding;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\QuickMathExplain;
use App\NativeComponents\Screens\QuickMathGame;
use App\NativeComponents\Screens\RecallGame;
use App\NativeComponents\Screens\Scene3dSmoke;
use App\NativeComponents\Screens\Settings;
use App\NativeComponents\Screens\SignalGame;
use App\NativeComponents\Screens\Splash;
use App\NativeComponents\Screens\VertexGame;
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
    Route::native('/play/quick-math/{session}/explain', QuickMathExplain::class)->name('native.play.quick-math.explain');
    Route::native('/play/recall/{session}', RecallGame::class)->name('native.play.recall');
    Route::native('/play/flow/{session}', FlowGame::class)->name('native.play.flow');
    Route::native('/play/signal/{session}', SignalGame::class)->name('native.play.signal');
    Route::native('/play/vertex/{session}', VertexGame::class)->name('native.play.vertex');
    Route::native('/play/axis/{session}', AxisGame::class)->name('native.play.axis');
    Route::native('/achievements', Achievements::class)->name('native.achievements');
    Route::native('/achievements/{category}', AchievementCategory::class)->name('native.achievements.category');
    Route::native('/profile', Profile::class)->name('native.profile');
    Route::native('/my-details', MyDetails::class)->name('native.my-details');
    Route::native('/settings', Settings::class)->name('native.settings');
    Route::native('/about', About::class)->name('native.about');

    // Development smoke test for the scene3d plugin. Remove this line, the
    // screen, its view, and the Settings entry point once the renderer is
    // trusted — it is not a product screen.
    Route::native('/dev/scene-3d', Scene3dSmoke::class)->name('native.dev.scene-3d');
});
