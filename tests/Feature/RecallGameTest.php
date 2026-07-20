<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\RecallGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startRecall(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'recall')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick through the ready + watch phases until the round is ready for input.
 */
function advanceToRecall($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'recall' && $guard++ < 40) {
        $screen->call('tickGame');
    }
}

test('the recall detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/recall')
        ->assertScreen(GameDetail::class)
        ->assertSee('Recall')
        ->tap('Play')
        ->follow()
        ->assertScreen(RecallGame::class)
        ->assertSet('phase', 'ready');
});

test('a full correct Recall playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startRecall($this->profile);

    $screen = Native::visit('/play/recall/'.$session->getKey())
        ->assertScreen(RecallGame::class);

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        advanceToRecall($screen);

        foreach ($screen->get('sequence') as $tile) {
            $screen->call('tapTile', (string) $tile);
        }

        // A correct sequence holds one tick, then the next tick advances.
        $screen->call('tickGame')->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('a wrong tap costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startRecall($this->profile);

    $screen = Native::visit('/play/recall/'.$session->getKey())
        ->assertSet('lives', 3);

    advanceToRecall($screen);

    $sequence = $screen->get('sequence');
    $tiles = $screen->get('tiles');
    $wrong = collect(range(0, $tiles - 1))->first(fn (int $tile): bool => $tile !== $sequence[0]);

    $screen->call('tapTile', (string) $wrong)
        ->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2);
});
