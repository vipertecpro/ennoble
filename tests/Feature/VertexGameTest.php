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

function startBarrage(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'vertex')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick through the stand-by countdown until a formation is descending.
 */
function advanceToWave($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'wave' && $guard++ < 20) {
        $screen->call('tickGame');
    }
}

/**
 * Ids of the invaders still standing that do / do not match the order.
 *
 * @return list<int>
 */
function standingIds($screen, bool $targets): array
{
    return collect($screen->get('invaders'))
        ->filter(fn (array $invader): bool => $invader['is_target'] === $targets)
        ->pluck('id')
        ->map(fn ($id): int => (int) $id)
        ->values()
        ->all();
}

test('the barrage detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/vertex')
        ->assertScreen(GameDetail::class)
        ->assertSee('Barrage')
        ->tap('Play')
        ->follow()
        ->assertScreen(VertexGame::class)
        ->assertSet('phase', 'ready');
});

test('clearing every target sweeps the wave and banks a clean round', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startBarrage($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey())
        ->assertScreen(VertexGame::class)
        ->assertSet('lives', 3)
        ->assertAccessible();

    advanceToWave($screen);

    $targets = standingIds($screen, targets: true);
    expect($targets)->not->toBeEmpty();

    foreach ($targets as $id) {
        $screen->call('fire', (string) $id);
    }

    // Clearing the last target resolves the wave without waiting for descent.
    $screen->assertSet('feedbackTone', 'swept')
        ->assertSet('lives', 3)
        ->assertSet('combo', 1);

    $round = $session->rounds()->reorder('round_number', 'desc')->first();

    expect($round->outcome)->toBe(RoundOutcome::Correct)
        ->and($round->response['hits'])->toBe(count($targets))
        ->and($round->response['false_alarms'])->toBe(0)
        ->and($round->response['survivors'])->toBe(0)
        // Resolved early, so descent was left on the clock and pays a bonus.
        ->and($round->response['remaining_fraction'])->toBeGreaterThan(0.0)
        ->and($round->score_delta)->toBeGreaterThan(count($targets) * 60);
});

test('firing on a decoy ends the wave immediately and costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startBarrage($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    advanceToWave($screen);

    $decoys = standingIds($screen, targets: false);
    expect($decoys)->not->toBeEmpty();

    $screen->call('fire', (string) $decoys[0]);

    // A false alarm must not be redeemable by clearing the rest afterwards.
    $screen->assertSet('feedbackTone', 'breached')
        ->assertSet('lives', 2)
        ->assertSet('combo', 0);

    $round = $session->rounds()->reorder('round_number', 'desc')->first();

    expect($round->outcome)->toBe(RoundOutcome::Incorrect)
        ->and($round->response['false_alarms'])->toBe(1);
});

test('a formation that lands with targets standing is a miss, not a false alarm', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startBarrage($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    advanceToWave($screen);

    $guard = 0;
    while ($screen->get('feedbackTone') === 'idle' && $guard++ < 60) {
        $screen->call('tickGame');
    }

    $screen->assertSet('feedbackTone', 'landed')
        ->assertSet('lives', 2);

    $round = $session->rounds()->reorder('round_number', 'desc')->first();

    expect($round->outcome)->toBe(RoundOutcome::Missed)
        ->and($round->response['survivors'])->toBeGreaterThan(0)
        // Patience is not slowness: a wave nobody finished has no reaction time.
        ->and($round->response_ms)->toBeNull()
        // Cast: JSON brings 0.0 back as an int, and toBe() is strict.
        ->and((float) $round->response['remaining_fraction'])->toBe(0.0);
});

test('shots are ignored once the wave has resolved', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startBarrage($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    advanceToWave($screen);

    $decoys = standingIds($screen, targets: false);
    $screen->call('fire', (string) $decoys[0])->assertSet('feedbackTone', 'breached');

    $roundsAfterBreach = $session->rounds()->count();

    // Panic-tapping through the reveal beat must not bank more evidence.
    foreach (standingIds($screen, targets: true) as $id) {
        $screen->call('fire', (string) $id);
    }

    expect($session->rounds()->count())->toBe($roundsAfterBreach)
        ->and($screen->get('lives'))->toBe(2);
});

test('a full clean playthrough completes the session with an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startBarrage($this->profile);
    $screen = Native::visit('/play/vertex/'.$session->getKey());

    $guard = 0;
    while ($screen->get('phase') !== 'result' && $guard++ < 200) {
        if ($screen->get('phase') === 'wave'
            && ! $screen->get('awaitingAdvance')
            && $screen->get('feedbackTone') === 'idle') {
            foreach (standingIds($screen, targets: true) as $id) {
                $screen->call('fire', (string) $id);
            }

            continue;
        }

        $screen->call('tickGame');
    }

    $screen->assertSet('phase', 'result');

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->score)->toBeGreaterThan(0)
        // Every wave swept clean, so no life was ever spent.
        ->and($session->incorrect_count)->toBe(0)
        ->and($session->missed_count)->toBe(0)
        ->and($session->best_combo)->toBeGreaterThan(1);
});
