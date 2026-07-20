<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\QuickMathExplain;
use App\NativeComponents\Screens\QuickMathGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startQuickMath(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'quick-math')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Type an integer answer on the keypad, digit by digit, then submit it.
 */
function typeQuickMathAnswer($screen, int $value): void
{
    foreach (str_split((string) $value) as $digit) {
        $screen->call('pressKey', $digit);
    }

    $screen->call('submitAnswer');
}

test('the quick math detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/quick-math')
        ->assertScreen(GameDetail::class)
        ->assertSee('Quick Math')
        ->tap('Play')
        ->follow()
        ->assertScreen(QuickMathGame::class)
        ->assertSet('phase', 'ready');
});

test('typing digits builds the answer and backspace trims it', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->call('pressKey', '4')
        ->call('pressKey', '2')
        ->assertSet('typedAnswer', '42')
        ->call('deleteKey')
        ->assertSet('typedAnswer', '4');
});

test('a full correct Quick Math playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->assertScreen(QuickMathGame::class)
        ->call('tickGame')
        ->assertSet('phase', 'playing');

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        typeQuickMathAnswer($screen, (int) $screen->get('answer'));
        // A correct answer holds for one tick, then the next tick advances.
        $screen->call('tickGame')->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('a wrong Quick Math answer costs a life and waits for the player', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->assertSet('lives', 3);

    typeQuickMathAnswer($screen, (int) $screen->get('answer') + 1);

    $screen->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2)
        ->assertSet('awaitingContinue', true);

    // The timer must not advance while the reveal waits for a decision.
    $screen->call('tickGame')->assertSet('awaitingContinue', true);

    $screen->call('continueRound')
        ->assertSet('awaitingContinue', false)
        ->assertSet('feedbackTone', 'idle');
});

test('a wrong answer auto-continues after the countdown', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing');

    typeQuickMathAnswer($screen, (int) $screen->get('answer') + 1);

    $screen->assertSet('awaitingContinue', true)
        ->assertSet('continueTicks', 3)
        ->call('tickGame')->assertSet('continueTicks', 2)
        ->call('tickGame')->assertSet('continueTicks', 1)
        // The third tick reaches zero and advances to the next round.
        ->call('tickGame')
        ->assertSet('awaitingContinue', false)
        ->assertSet('feedbackTone', 'idle')
        ->assertSet('roundIndex', 1);
});

test('opening the explanation opens the chat while a reveal is showing', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing');

    typeQuickMathAnswer($screen, (int) $screen->get('answer') + 1);

    $screen->assertSet('awaitingContinue', true)
        ->call('openExplain')
        ->assertNavigatedTo('/play/quick-math/'.$session->getKey().'/explain');
});

test('returning from the explanation restarts a fresh auto-continue countdown', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing');

    typeQuickMathAnswer($screen, (int) $screen->get('answer') + 1);

    // Countdown has run down a little…
    $screen->call('tickGame')->assertSet('continueTicks', 2);

    // …and returning from the explanation resets it to the full 3 seconds.
    $screen->call('onResume')
        ->assertSet('continuePaused', false)
        ->assertSet('continueTicks', 3)
        ->assertSet('awaitingContinue', true);
});

test('the close control exits natively to the games library', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->call('exit')
        ->assertReplacedWith('/games');
});

test('the explain screen breaks the problem down step by step', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    Native::visit('/play/quick-math/'.$session->getKey().'/explain', [
        'expression' => '7 × 3',
        'answer' => 21,
    ])
        ->assertScreen(QuickMathExplain::class)
        ->assertSet('answer', 21);
});
