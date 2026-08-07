<?php

use App\Domain\Games\Axis\AxisFigure;
use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\AxisGame;
use App\NativeComponents\Screens\GameDetail;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startAxis(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'axis')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

function axisAdvanceToPlaying($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'playing' && $guard++ < 20) {
        $screen->call('tickGame');
    }
}

/** The scene3d element carries the whole scene as one JSON prop. */
function axisScene($screen): array
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

    expect($element)->not->toBeNull('No scene_3d element — the 3D viewport is missing.');

    return json_decode($element['props']['scene'], true, flags: JSON_THROW_ON_ERROR);
}

test('the axis detail screen launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/axis')
        ->assertScreen(GameDetail::class)
        ->assertSee('Axis')
        ->tap('Play')
        ->follow()
        ->assertScreen(AxisGame::class)
        ->assertSet('phase', 'ready');
});

test('the scene puts three figures on the wire and only the candidates are tappable', function () {
    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey());
    axisAdvanceToPlaying($screen);

    $nodes = collect(axisScene($screen)['n']);

    $sides = $nodes->map(fn (array $n): string => strstr($n['id'], ':', true))->unique()->sort()->values();

    expect($sides->all())->toBe(['a', 'b', 'ref']);

    // The reference is not an answer. If it were tappable the player could
    // resolve a round by tapping the question.
    expect($nodes->where('tap', 1)->map(fn (array $n): string => strstr($n['id'], ':', true))->unique()->sort()->values()->all())
        ->toBe(['a', 'b']);
});

test('the rendered candidates really are one rotation and one mirror', function () {
    // The screen picks which side gets the match, so this is the only place
    // that check covers what the player is actually shown rather than what the
    // generator produced.
    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey());
    axisAdvanceToPlaying($screen);

    $round = $screen->get('rounds')[$screen->get('roundIndex')];
    $answer = $screen->get('answer');

    $reference = new AxisFigure($round['cells']);
    $match = new AxisFigure($round['matchCells']);
    $decoy = new AxisFigure($round['decoyCells']);

    expect($match->isSameShapeAs($reference))->toBeTrue()
        ->and($decoy->isSameShapeAs($reference))->toBeFalse()
        ->and($answer)->toBeIn(['a', 'b']);
});

test('tapping any cube of a figure votes for that whole figure', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey());
    axisAdvanceToPlaying($screen);

    // The renderer reports the id of the individual cube that was hit, never
    // the figure — "b:4" must resolve the round exactly as "b:0" would.
    $screen->call('chooseFigure', $screen->get('answer').':4');

    expect($screen->get('feedbackTone'))->toBe('correct');

    $round = GameSession::find($session->getKey())->rounds()->first();

    expect($round->outcome)->toBe(RoundOutcome::Correct)
        ->and($round->response['chosen'])->toBe($screen->get('answer'))
        // The figure's size is stored as evidence because the score depends on
        // it; re-deriving it later from the level would rescore history.
        ->and($round->response['cubes'])->toBeGreaterThanOrEqual(4);
});

test('a tap on the reference cannot resolve a round', function () {
    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey());
    axisAdvanceToPlaying($screen);

    $screen->call('chooseFigure', 'ref:0');

    expect($screen->get('feedbackTone'))->toBe('idle')
        ->and(GameSession::find($session->getKey())->rounds()->count())->toBe(0);
});

test('a full correct Axis playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey())
        ->assertScreen(AxisGame::class);

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        axisAdvanceToPlaying($screen);

        $screen->call('chooseFigure', $screen->get('answer').':0');
        $screen->call('tickGame')->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds);

    expect(GameSession::find($session->getKey())->status)->toBe(SessionStatus::Completed)
        ->and($screen->get('resultScore'))->toBeGreaterThan(0);
});

test('a wrong figure costs a life and breaks the combo', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startAxis($this->profile);

    $screen = Native::visit('/play/axis/'.$session->getKey());
    axisAdvanceToPlaying($screen);

    $wrong = $screen->get('answer') === 'a' ? 'b' : 'a';
    $screen->call('chooseFigure', $wrong.':0');

    expect($screen->get('feedbackTone'))->toBe('wrong')
        ->and($screen->get('lives'))->toBe($screen->get('maxLives') - 1)
        ->and($screen->get('combo'))->toBe(0);
});
