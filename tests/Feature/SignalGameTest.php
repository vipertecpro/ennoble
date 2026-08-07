<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Signal\SignalPalette;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\SignalGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startSignal(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'signal')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/**
 * Tick through the ready countdown until a stimulus is live and answerable.
 */
function advanceToPlaying($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'playing' && $guard++ < 20) {
        $screen->call('tickGame');
    }
}

/**
 * Flatten a rendered EDGE tree so a single node can be located by its props.
 *
 * @param  array<string, mixed>  $node
 * @return list<array<string, mixed>>
 */
function signalFlatten(array $node): array
{
    $nodes = [$node];

    foreach ($node['children'] ?? [] as $child) {
        $nodes = [...$nodes, ...signalFlatten($child)];
    }

    return $nodes;
}

test('the signal detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/signal')
        ->assertScreen(GameDetail::class)
        ->assertSee('Signal')
        ->tap('Play')
        ->follow()
        ->assertScreen(SignalGame::class)
        ->assertSet('phase', 'ready');
});

test('a full correct Signal playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startSignal($this->profile);

    $screen = Native::visit('/play/signal/'.$session->getKey())
        ->assertScreen(SignalGame::class);

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        advanceToPlaying($screen);

        $screen->call('chooseOption', $screen->get('answer'));

        // A resolved round holds its reveal beat, then the next tick advances.
        $screen->call('tickGame')->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds);

    // The result stat tiles must be sized by CONTENT + padding, never by a
    // height with `justify-center`: on iOS a fixed-height column ignores
    // justify-content and pins its children to the bottom edge, which is what
    // made the labels read as clipped. Padding on both ends is the invariant
    // that keeps the text off the tile's edges — assert it reached the wire.
    $tiles = collect(signalFlatten($screen->tree()))
        ->filter(fn (array $node): bool => collect($node['children'] ?? [])
            ->contains(fn (array $child): bool => in_array(
                data_get($child, 'props.text'),
                ['Accuracy', 'Correct', 'Best combo'],
                true,
            )));

    expect($tiles)->toHaveCount(3);

    foreach ($tiles as $tile) {
        // padding is [top, right, bottom, left].
        $padding = data_get($tile, 'layout.padding');

        expect(data_get($tile, 'layout.height'))->toBeNull()
            ->and($padding[0])->toBeGreaterThan(0)
            ->and($padding[2])->toBeGreaterThan(0);
    }

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('naming the word when the rule says ink costs a life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startSignal($this->profile);

    $screen = Native::visit('/play/signal/'.$session->getKey())
        ->assertSet('lives', 3)
        ->assertAccessible();

    advanceToPlaying($screen);

    // The competing half of the stimulus is always an available option, so it
    // is the honest "fell for the interference" tap.
    $decoy = $screen->get('rule') === 'ink' ? $screen->get('word') : $screen->get('ink');

    $screen->call('chooseOption', $decoy)
        ->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2)
        ->assertSet('combo', 0);
});

test('a round that runs out of time is a missed life', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startSignal($this->profile);

    $screen = Native::visit('/play/signal/'.$session->getKey());

    advanceToPlaying($screen);

    $guard = 0;
    while ($screen->get('feedbackTone') === 'idle' && $guard++ < 40) {
        $screen->call('tickGame');
    }

    $screen->assertSet('feedbackTone', 'timeout')
        ->assertSet('lives', 2);

    expect($session->rounds()->reorder('round_number', 'desc')->first()->outcome)
        ->toBe(RoundOutcome::Missed);
});

test('the stimulus paints the word in its mismatched ink', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startSignal($this->profile);

    $screen = Native::visit('/play/signal/'.$session->getKey());

    advanceToPlaying($screen);

    $word = $screen->get('word');
    $ink = $screen->get('ink');

    expect($ink)->not->toBe($word);

    $expectedLabel = 'The word '.SignalPalette::label($word)
        .', printed in '.SignalPalette::label($ink).' ink';

    $stimulus = collect(signalFlatten($screen->tree()))
        ->first(fn (array $node): bool => data_get($node, 'props.a11y_label') === $expectedLabel);

    // The whole game lives in this mismatch: the label is the word, the ink is
    // a different color, and the screen reader is told both.
    expect($stimulus)->not->toBeNull()
        ->and(data_get($stimulus, 'props.text'))->toBe(SignalPalette::label($word))
        ->and(data_get($stimulus, 'props.color'))->toBe(SignalPalette::hex($ink));
});

test('answering correctly through a rule switch pays a bonus', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startSignal($this->profile);

    $screen = Native::visit('/play/signal/'.$session->getKey());

    // Play until a round arrives whose rule flipped from the previous one.
    $guard = 0;
    while ($guard++ < 12) {
        advanceToPlaying($screen);

        $switched = $screen->get('ruleSwitched');
        $combo = $screen->get('combo');

        $screen->call('chooseOption', $screen->get('answer'));

        if ($switched) {
            $delta = $session->rounds()->reorder('round_number', 'desc')->first()->score_delta;

            // 100 base + speed bonus (0-50) + combo + the 25 switch bonus.
            expect($delta)->toBeGreaterThanOrEqual(125 + min(($combo + 1) * 10, 120));

            return;
        }

        $screen->call('tickGame')->call('tickGame');
    }

    $this->fail('The intermediate level never presented a rule switch.');
});
