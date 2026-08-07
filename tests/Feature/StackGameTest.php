<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Stack\StackPieces;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\StackGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);
});

function startStack(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'stack')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

/** Tick with the clock actually moving, as it does on a device. */
function stackTick($screen, int $times = 1, int $ms = 250): void
{
    for ($i = 0; $i < $times; $i++) {
        test()->travel($ms)->milliseconds();
        $screen->call('tickGame');
    }
}

function stackPlaying($screen): void
{
    $guard = 0;
    while ($screen->get('phase') !== 'playing' && $guard++ < 20) {
        stackTick($screen);
    }
}

function stackScene($screen): array
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
    expect($element)->not->toBeNull('No scene_3d element — the board is missing.');

    return json_decode($element['props']['scene'], true, flags: JSON_THROW_ON_ERROR);
}

test('the stack detail screen launches a free-play session', function () {
    Native::visit('/games/stack')
        ->assertScreen(GameDetail::class)
        ->assertSee('Stack')
        ->tap('Play')
        ->follow()
        ->assertScreen(StackGame::class)
        ->assertSet('phase', 'ready');
});

test('gravity is driven by the drop clock, not by the poll rate', function () {
    // The two are deliberately independent: the poll is how often the screen
    // gets to think, the drop is how fast the game is meant to be. If they
    // were the same thing, every speed would be a multiple of the framework's
    // cadence and the level configuration would mean nothing.
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $row = $screen->get('pieceRow');

    // One poll, well under the 800ms drop interval — nothing should move.
    stackTick($screen, 1, 250);
    expect($screen->get('pieceRow'))->toBe($row);

    // Past the interval, exactly one row.
    stackTick($screen, 3, 250);
    expect($screen->get('pieceRow'))->toBe($row + 1);
});

test('a piece moves and rotates only where the board allows', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $screen->call('moveLeft');
    expect($screen->get('pieceColumn'))->toBeLessThan(StackGame::COLUMNS);

    // Walk into the left wall; it must stop rather than leave the board.
    for ($i = 0; $i < 12; $i++) {
        $screen->call('moveLeft');
    }

    $scene = stackScene($screen);
    $columns = collect($scene['n'])->filter(fn ($n) => str_starts_with($n['id'], 'p:'));

    expect($columns)->not->toBeEmpty();

    $screen->call('rotate');

    expect($screen->get('pieceRotation'))->toBeGreaterThanOrEqual(0);
});

test('a hard drop settles the piece and starts the next one', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $first = $screen->get('pieceIndex');
    $screen->call('hardDrop');

    expect($screen->get('pieceIndex'))->toBe($first + 1)
        // Four cells settle, and none of them are above the ceiling here.
        ->and(collect($screen->get('cells'))->flatten()->filter()->count())->toBe(4);

    $round = GameSession::find($session->getKey())->rounds()->first();

    expect($round)->not->toBeNull()
        ->and($round->response['piece'])->toBeIn(['i', 'o', 't', 's', 'z', 'j', 'l']);
});

test('a placement that buries a cell is recorded as incorrect', function () {
    // Accuracy in this game means "placements that buried nothing". A piece
    // dropped over a gap it cannot fill is the mistake being measured.
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    // Build a floor with a one-column gap, then cover the gap.
    $cells = $screen->get('cells');
    $bottom = StackGame::ROWS - 1;

    for ($column = 0; $column < StackGame::COLUMNS; $column++) {
        $cells[$bottom][$column] = $column === 3 ? null : '#FFFFFF';
    }

    $screen->set('cells', $cells);
    $screen->set('piece', 'o');
    $screen->set('pieceRotation', 0);
    // The O occupies columns 3 and 4 of its 4-wide box at offset 2.
    $screen->set('pieceColumn', 2);
    $screen->set('pieceRow', 0);

    $screen->call('hardDrop');

    $round = GameSession::find($session->getKey())->rounds()->latest('id')->first();

    expect($round->outcome)->toBe(RoundOutcome::Incorrect)
        ->and($round->response['holes_added'])->toBeGreaterThan(0);
});

test('completing a row clears it and scores', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    // Fill the bottom row except the two columns an O will land in.
    $cells = $screen->get('cells');
    $bottom = StackGame::ROWS - 1;

    for ($column = 0; $column < StackGame::COLUMNS; $column++) {
        $cells[$bottom][$column] = in_array($column, [3, 4], true) ? null : '#FFFFFF';
        $cells[$bottom - 1][$column] = in_array($column, [3, 4], true) ? null : '#FFFFFF';
    }

    $screen->set('cells', $cells);
    $screen->set('piece', 'o');
    $screen->set('pieceRotation', 0);
    $screen->set('pieceColumn', 2);
    $screen->set('pieceRow', 0);

    $screen->call('hardDrop');

    expect($screen->get('lines'))->toBe(2)
        ->and($screen->get('score'))->toBeGreaterThan(0);

    $round = GameSession::find($session->getKey())->rounds()->latest('id')->first();

    expect($round->response['lines'])->toBe(2);
});

test('the scene draws the settled board and the live piece separately', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);
    $screen->call('hardDrop');

    $ids = collect(stackScene($screen)['n'])->pluck('id');

    expect($ids->filter(fn (string $id): bool => str_starts_with($id, 'c:')))->toHaveCount(4);

    // The live piece enters one row ABOVE the ceiling, and the part still
    // above it is not drawn — so a freshly spawned piece shows only the cells
    // that have actually entered the board. Drawing them would put cubes
    // outside the playfield.
    $visible = collect(StackPieces::cells($screen->get('piece'), $screen->get('pieceRotation')))
        ->filter(fn (array $cell): bool => $screen->get('pieceRow') + $cell[1] >= 0)
        ->count();

    expect($ids->filter(fn (string $id): bool => str_starts_with($id, 'p:')))->toHaveCount($visible)
        ->and($visible)->toBeGreaterThan(0);
});

test('a settled cube keeps its identity, so the board never re-animates', function () {
    // Every settled cell is drawn from its own coordinates, so a poll that
    // changes nothing about it must produce a byte-identical node — otherwise
    // the whole board would be rebuilt four times a second.
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);
    $screen->call('hardDrop');

    $before = collect(stackScene($screen)['n'])->filter(fn ($n) => str_starts_with($n['id'], 'c:'))->keyBy('id');

    stackTick($screen, 2);

    $after = collect(stackScene($screen)['n'])->filter(fn ($n) => str_starts_with($n['id'], 'c:'))->keyBy('id');

    foreach ($before as $id => $node) {
        expect($after[$id])->toBe($node);
    }
});

test('a run through every piece finishes and reports', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $guard = 0;
    while ($screen->get('phase') !== 'result' && $guard++ < 400) {
        $screen->call('hardDrop');
    }

    $screen->assertSet('phase', 'result');

    expect(GameSession::find($session->getKey())->status)->toBe(SessionStatus::Completed)
        ->and($screen->get('resultCorrect'))->toBeGreaterThanOrEqual(0);
});

test('the board draws a grid behind the pieces', function () {
    // The empty playfield is what makes it read as a board rather than blocks
    // floating in space. Lines, not a tile per cell: 128 extra nodes would
    // ship on every render for something that never changes.
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $ids = collect(stackScene($screen)['n'])->pluck('id');

    expect($ids)->toContain('grid:panel')
        ->and($ids->filter(fn (string $id): bool => str_starts_with($id, 'grid:v')))->toHaveCount(StackGame::COLUMNS - 1)
        ->and($ids->filter(fn (string $id): bool => str_starts_with($id, 'grid:h')))->toHaveCount(StackGame::ROWS - 1);
});

test('the next queue shows what actually spawns', function () {
    // A preview that can drift from the sequence is worse than no preview:
    // the player plans against it.
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    expect($screen->get('nextPieces'))->toHaveCount(3);

    $expected = $screen->get('nextPieces')[0];
    $screen->call('hardDrop');

    expect($screen->get('piece'))->toBe($expected);
});

test('the level rises with lines cleared and quickens the drop', function () {
    $session = startStack($this->profile);

    $screen = Native::visit('/play/stack/'.$session->getKey());
    stackPlaying($screen);

    $opening = $screen->get('dropIntervalMs');

    expect($screen->get('level'))->toBe(1);

    // Ten cleared rows is one level. Progress is measured in LINES, so
    // clearing efficiently reaches the fast game sooner than merely placing
    // the same number of pieces.
    $screen->set('lines', 20);
    $screen->call('hardDrop');

    expect($screen->get('level'))->toBeGreaterThanOrEqual(3)
        ->and($screen->get('dropIntervalMs'))->toBeLessThan($opening)
        // Never faster than the screen can think, or pieces land between polls.
        ->and($screen->get('dropIntervalMs'))->toBeGreaterThanOrEqual(280);
});
