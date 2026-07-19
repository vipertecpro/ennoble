<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
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

test('the quick math detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/quick-math')
        ->assertScreen(GameDetail::class)
        ->assertSee('Quick Math')
        ->assertSee('How to play')
        ->assertAccessible()
        ->tap('Play')
        ->follow()
        ->assertScreen(QuickMathGame::class)
        ->assertSet('phase', 'ready');
});

test('a full correct Quick Math playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->assertScreen(QuickMathGame::class)
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->assertAccessible();

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        $answer = (string) $screen->get('answer');
        $screen->call('chooseOption', $answer)->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds)
        ->assertAccessible();

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('a wrong Quick Math answer costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startQuickMath($this->profile);

    $screen = Native::visit('/play/quick-math/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->assertSet('lives', 3);

    $answer = $screen->get('answer');
    $wrong = (string) collect($screen->get('options'))->first(fn (int $option): bool => $option !== $answer);

    $screen->call('chooseOption', $wrong)
        ->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2);
});
