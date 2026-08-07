<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\VertexGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startVertex(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'vertex')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick through the ready countdown until an object is in flight.
 */
function advanceToFlight($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'flight' && $guard++ < 20) {
        $screen->call('tickGame');
    }
}

/**
 * Let the current object fly all the way past without striking it.
 */
function letObjectPass($screen): void
{
    $guard = 0;
    while ($screen->get('feedbackTone') === 'idle' && $guard++ < 40) {
        $screen->call('tickGame');
    }
}

test('the vertex detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/vertex')
        ->assertScreen(GameDetail::class)
        ->assertSee('Vertex')
        ->tap('Play')
        ->follow()
        ->assertScreen(VertexGame::class)
        ->assertSet('phase', 'ready');
});

test('striking a target scores it and striking a decoy costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startVertex($this->profile);

    $screen = Native::visit('/play/vertex/'.$session->getKey())
        ->assertScreen(VertexGame::class)
        ->assertSet('lives', 3)
        ->assertAccessible();

    $struckTarget = false;
    $struckDecoy = false;
    $guard = 0;

    // Walk the stream, striking every object regardless of form — that
    // deliberately lands both a hit and the false alarm the game exists to
    // provoke, so both branches get exercised on real evidence.
    while ($guard++ < 12 && $screen->get('phase') !== 'result') {
        advanceToFlight($screen);

        if ($screen->get('phase') !== 'flight') {
            break;
        }

        $wasGo = $screen->get('isGo');
        $screen->call('strike');

        if ($wasGo) {
            $struckTarget = true;
            expect($screen->get('feedbackTone'))->toBe('struck');
        } else {
            $struckDecoy = true;
            expect($screen->get('feedbackTone'))->toBe('false-alarm')
                ->and($screen->get('combo'))->toBe(0);
        }

        $screen->call('tickGame')->call('tickGame')->call('tickGame');
    }

    expect($struckTarget)->toBeTrue()
        ->and($struckDecoy)->toBeTrue();

    // Three false strikes end the run early, so lives must have been spent.
    expect($screen->get('lives'))->toBeLessThan(3);
});

test('letting a decoy fly past is scored as correct, not as a miss', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startVertex($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    $guard = 0;
    while ($guard++ < 12 && $screen->get('phase') !== 'result') {
        advanceToFlight($screen);

        if ($screen->get('phase') !== 'flight') {
            break;
        }

        if (! $screen->get('isGo')) {
            letObjectPass($screen);

            $screen->assertSet('feedbackTone', 'passed')
                ->assertSet('lives', 3);

            $round = $session->rounds()->reorder('round_number', 'desc')->first();

            expect($round->outcome)->toBe(RoundOutcome::Correct)
                ->and($round->response['is_go'])->toBeFalse()
                ->and($round->response['struck'])->toBeFalse()
                // Discipline is not slowness: a clean pass has no reaction time.
                ->and($round->response_ms)->toBeNull()
                ->and($round->score_delta)->toBeGreaterThan(0);

            return;
        }

        $screen->call('strike')->call('tickGame')->call('tickGame')->call('tickGame');
    }

    $this->fail('The intermediate level never presented a decoy.');
});

test('a target allowed through is a missed life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startVertex($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    advanceToFlight($screen);

    $guard = 0;
    while (! $screen->get('isGo') && $guard++ < 12) {
        $screen->call('strike')->call('tickGame')->call('tickGame')->call('tickGame');
        advanceToFlight($screen);
    }

    expect($screen->get('isGo'))->toBeTrue();

    letObjectPass($screen);

    $screen->assertSet('feedbackTone', 'missed')
        ->assertSet('lives', 2);

    expect($session->rounds()->reorder('round_number', 'desc')->first()->outcome)
        ->toBe(RoundOutcome::Missed);
});

test('a struck target records its depth and completing the run scores it', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startVertex($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    // Play it properly: strike targets, hold for decoys.
    $guard = 0;
    while ($screen->get('phase') !== 'result' && $guard++ < 200) {
        if ($screen->get('phase') === 'flight'
            && ! $screen->get('awaitingAdvance')
            && $screen->get('feedbackTone') === 'idle'
            && $screen->get('isGo')) {
            $screen->call('strike');

            continue;
        }

        $screen->call('tickGame');
    }

    $screen->assertSet('phase', 'result');

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->score)->toBeGreaterThan(0)
        // A clean run never spends a life, so every round was judged correctly.
        ->and($session->incorrect_count)->toBe(0)
        ->and($session->missed_count)->toBe(0);

    $struck = $session->rounds()->get()
        ->first(fn ($round): bool => ($round->response['struck'] ?? false) === true);

    expect($struck)->not->toBeNull()
        ->and($struck->response['depth'])->toBeGreaterThan(0.0)
        ->and($struck->response['depth'])->toBeLessThanOrEqual(1.0);
});
