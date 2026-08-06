<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\FlowGame;
use App\NativeComponents\Screens\GameDetail;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startFlow(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'flow')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick through the ready phase until a current is live and awaiting a swipe.
 */
function advanceToFlow($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'flow' && $guard++ < 20) {
        $screen->call('tickGame');
    }
}

test('the flow detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/flow')
        ->assertScreen(GameDetail::class)
        ->assertSee('Flow')
        ->tap('Play')
        ->follow()
        ->assertScreen(FlowGame::class)
        ->assertSet('phase', 'ready');
});

test('a full correct Flow playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startFlow($this->profile);

    $screen = Native::visit('/play/flow/'.$session->getKey())
        ->assertScreen(FlowGame::class);

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        advanceToFlow($screen);

        $screen->call('handleSwipe', $screen->get('currentDirection'));

        // A correct current holds its reveal beat, then the next tick advances.
        $screen->call('tickGame')->call('tickGame')->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('a swipe in the wrong direction costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startFlow($this->profile);

    $screen = Native::visit('/play/flow/'.$session->getKey())
        ->assertSet('lives', 3)
        ->assertAccessible();

    advanceToFlow($screen);

    $wrong = collect(['left', 'right', 'up', 'down'])
        ->first(fn (string $direction): bool => $direction !== $screen->get('currentDirection'));

    $screen->call('handleSwipe', $wrong)
        ->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2);
});

test('a current that reaches the light un-swiped is a missed life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startFlow($this->profile);

    $screen = Native::visit('/play/flow/'.$session->getKey());

    advanceToFlow($screen);

    // Let the whole window elapse without swiping.
    $guard = 0;
    while ($screen->get('feedbackTone') === 'idle' && $guard++ < 40) {
        $screen->call('tickGame');
    }

    $screen->assertSet('feedbackTone', 'timeout')
        ->assertSet('lives', 2);
});
