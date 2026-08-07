<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Leap\LeapGenerator;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\LeapGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);
});

function startLeap(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'leap')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick the game with time actually moving. The screen polls every 250ms on a
 * device; nothing here advances on its own, so the clock is travelled by the
 * same amount before each tick.
 */
function leapTick($screen, int $times = 1, int $ms = 250): void
{
    for ($i = 0; $i < $times; $i++) {
        test()->travel($ms)->milliseconds();
        $screen->call('tickGame');
    }
}

function leapRunning($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'running' && $guard++ < 20) {
        leapTick($screen);
    }
}

function leapScene($screen): array
{
    $find = function (array $node) use (&$find): ?array {
        if (($node['type'] ?? null) === 'scene_3d') {
            return $node;
        }
        foreach ($node['children'] ?? [] as $child) {
            if ($found = $find($child)) {
                return $found;
            }
        }

        return null;
    };

    $element = $find($screen->tree());
    expect($element)->not->toBeNull('No scene_3d element — the viewport is missing.');

    return json_decode($element['props']['scene'], true, flags: JSON_THROW_ON_ERROR);
}

test('the leap detail screen launches a free-play session', function () {
    Native::visit('/games/leap')
        ->assertScreen(GameDetail::class)
        ->assertSee('Leap')
        ->tap('Play')
        ->follow()
        ->assertScreen(LeapGame::class)
        ->assertSet('phase', 'ready');
});

test('no gap is short enough for one jump to clear two obstacles', function () {
    // The whole difficulty model rests on this. If a gap ever falls inside the
    // airborne window, a single tap clears two obstacles and the player is
    // rewarded for a mistake.
    $level = new GameLevel;
    $level->configuration = ['travel_ms' => 2500, 'min_travel_ms' => 1450, 'max_height' => 3];

    $course = (new LeapGenerator)->generate($level, 'leap-seed', 40);

    foreach ($course as $index => $obstacle) {
        if ($index === 0) {
            continue;
        }

        expect($obstacle['gap_ms'])->toBeGreaterThan(
            LeapGame::CLEAR_WINDOW_END_MS,
            "Obstacle {$index} arrives {$obstacle['gap_ms']}ms after the last — inside one jump.",
        );
    }
});

test('the course speeds up from first obstacle to last', function () {
    $level = new GameLevel;
    $level->configuration = ['travel_ms' => 2500, 'min_travel_ms' => 1450];

    $course = (new LeapGenerator)->generate($level, 'ramp', 20);

    expect($course[0]['travel_ms'])->toBe(2500)
        ->and(end($course)['travel_ms'])->toBe(1450)
        // Monotonic: a run that got easier partway through would read as a bug.
        // sliding() preserves the original keys, so the pair has to be
        // re-indexed before it can be compared positionally.
        ->and(collect($course)->pluck('travel_ms')->sliding(2)->every(
            fn ($pair) => $pair->values()[1] <= $pair->values()[0]
        ))->toBeTrue();
});

test('a course is deterministic for a seed', function () {
    $level = new GameLevel;
    $level->configuration = ['travel_ms' => 2500, 'min_travel_ms' => 1450];
    $generator = new LeapGenerator;

    expect($generator->generate($level, 'same', 12))->toBe($generator->generate($level, 'same', 12))
        ->and($generator->generate($level, 'other', 12))->not->toBe($generator->generate($level, 'same', 12));
});

test('the scene carries a ground, a runner, and the live obstacles', function () {
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);
    leapTick($screen, 8);

    $ids = collect(leapScene($screen)['n'])->pluck('id');

    expect($ids)->toContain('ground')
        ->and($ids)->toContain('runner');

    // The ground is a stretched box — non-uniform scale, which the plugin only
    // gained for this. Without sy/sz it renders as a cube.
    $ground = collect(leapScene($screen)['n'])->firstWhere('id', 'ground');

    expect($ground)->toHaveKey('sy')
        ->and($ground['sy'])->not->toBe($ground['s']);
});

test('an obstacle handed to the renderer keeps the same revision while it crosses', function () {
    // THE property the whole genre depends on. PHP re-sends the scene every
    // poll; if a rebuilt obstacle came back with a different revision, the
    // renderer would restart its tween and it would visibly stutter or jump
    // back to the start four times a second.
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);
    leapTick($screen, 8);

    $before = collect(leapScene($screen)['n'])->filter(fn ($n) => str_starts_with($n['id'], 'ob:'));

    expect($before)->not->toBeEmpty('No obstacle was handed over.');

    leapTick($screen, 2);

    $after = collect(leapScene($screen)['n'])->keyBy('id');

    foreach ($before as $obstacle) {
        expect($after[$obstacle['id']])->toBe($obstacle);
    }
});

test('a jump timed to an arrival clears it, and no jump collides', function () {
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);

    // Let the first obstacle be handed over, then never jump.
    $guard = 0;
    while ($screen->get('resolvedCount') === 0 && $guard++ < 60) {
        leapTick($screen);
    }

    expect($screen->get('resolvedCount'))->toBe(1)
        ->and($screen->get('lives'))->toBe($screen->get('maxLives') - 1)
        ->and($screen->get('combo'))->toBe(0);

    $round = GameSession::find($session->getKey())->rounds()->first();

    expect($round->outcome)->toBe(RoundOutcome::Incorrect)
        // A collision has no jump to measure, so there is no lead time.
        ->and($round->response_ms)->toBeNull()
        ->and($round->response['cleared'])->toBeFalse();
});

test('jumping again mid-air is ignored, so mashing cannot beat timing', function () {
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);

    $screen->call('jump');
    $first = $screen->get('jumpStartedAtMs');

    expect($first)->toBeGreaterThan(0);

    $screen->call('jump');

    expect($screen->get('jumpStartedAtMs'))->toBe($first);
});

test('a run ends and reports through the shared result screen', function () {
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);

    // Never jumping burns a life per obstacle; three lives ends the run.
    $guard = 0;
    while ($screen->get('phase') !== 'result' && $guard++ < 400) {
        leapTick($screen);
    }

    $screen->assertSet('phase', 'result');

    expect($screen->get('lives'))->toBe(0)
        ->and(GameSession::find($session->getKey())->status)->toBe(SessionStatus::Completed);
});

test('a jump landing inside the window clears the obstacle', function () {
    // The positive half of the collision arithmetic, and the one the player
    // actually experiences. Driven off the obstacle's own recorded arrival
    // time, so it tests the model rather than restating it.
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);

    $guard = 0;
    while ($screen->get('active') === [] && $guard++ < 60) {
        leapTick($screen);
    }

    $obstacle = $screen->get('active')[0];
    $lead = 300;

    // Stand still until the jump should start, then jump.
    $wait = $obstacle['arrives_at_ms'] - $lead - (int) now()->getTimestampMs();
    test()->travel(max(0, $wait))->milliseconds();
    $screen->call('jump');

    // Tick past the arrival so it gets adjudicated.
    leapTick($screen, 4);

    expect($screen->get('resolvedCount'))->toBe(1)
        ->and($screen->get('lives'))->toBe($screen->get('maxLives'))
        ->and($screen->get('combo'))->toBe(1);

    $round = GameSession::find($session->getKey())->rounds()->first();

    expect($round->outcome)->toBe(RoundOutcome::Correct)
        // The lead time is evidence: how far ahead of the obstacle the jump
        // began, which is what the result screen averages.
        ->and($round->response_ms)->toBeGreaterThan(0)
        ->and($round->response['cleared'])->toBeTrue();
});

test('a jump too early has landed again by the time the obstacle arrives', function () {
    // Jumping the moment you see something coming is the beginner mistake the
    // window exists to punish; without an upper bound it would be optimal.
    $session = startLeap($this->profile);

    $screen = Native::visit('/play/leap/'.$session->getKey());
    leapRunning($screen);

    $guard = 0;
    while ($screen->get('active') === [] && $guard++ < 60) {
        leapTick($screen);
    }

    $obstacle = $screen->get('active')[0];

    // Well outside CLEAR_WINDOW_END_MS.
    $wait = $obstacle['arrives_at_ms'] - 1600 - (int) now()->getTimestampMs();
    test()->travel(max(0, $wait))->milliseconds();
    $screen->call('jump');

    leapTick($screen, 8);

    expect($screen->get('resolvedCount'))->toBe(1)
        ->and($screen->get('lives'))->toBe($screen->get('maxLives') - 1);
});
