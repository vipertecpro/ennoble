<?php

use App\Domain\Games\Stack\StackBoard;
use App\Domain\Games\Stack\StackGenerator;
use App\Domain\Games\Stack\StackPieces;
use App\Models\GameLevel;

/** Fill a whole row except one column, so a single cell completes it. */
function boardWithRow(StackBoard $board, int $row, int $skipColumn): StackBoard
{
    for ($column = 0; $column < $board->columns; $column++) {
        if ($column === $skipColumn) {
            continue;
        }

        $board = $board->lock([[0, 0]], $column, $row, '#FFFFFF');
    }

    return $board;
}

test('every piece has four cells in all four rotations', function () {
    // A tetromino with three or five cells would be a typo in the table, and
    // it would only show up as a piece that looks wrong on screen.
    foreach (StackPieces::keys() as $piece) {
        for ($rotation = 0; $rotation < 4; $rotation++) {
            expect(StackPieces::cells($piece, $rotation))
                ->toHaveCount(4, "Piece [{$piece}] rotation {$rotation} is not a tetromino.");
        }
    }
});

test('rotation wraps in both directions', function () {
    foreach (StackPieces::keys() as $piece) {
        expect(StackPieces::cells($piece, 4))->toBe(StackPieces::cells($piece, 0))
            // Negative rotation happens the moment anyone adds a
            // rotate-counter-clockwise control.
            ->and(StackPieces::cells($piece, -1))->toBe(StackPieces::cells($piece, 3));
    }
});

test('the O piece never moves when rotated', function () {
    // The tell-tale sign of a generic matrix rotation being applied to the
    // piece table: an O that wobbles as it spins.
    for ($rotation = 0; $rotation < 4; $rotation++) {
        expect(StackPieces::cells('o', $rotation))->toBe(StackPieces::cells('o', 0));
    }
});

test('a piece is refused outside the walls and floor but allowed above the ceiling', function () {
    $board = StackBoard::empty(8, 16);
    $cells = StackPieces::cells('o', 0);

    expect($board->accepts($cells, 0, 0))->toBeTrue()
        ->and($board->accepts($cells, -2, 0))->toBeFalse()
        ->and($board->accepts($cells, 7, 0))->toBeFalse()
        ->and($board->accepts($cells, 0, 15))->toBeFalse()
        // Above the board is legal: a piece enters partly off the top, and
        // rejecting that would end a run one piece before the stack actually
        // reached the ceiling.
        ->and($board->accepts($cells, 0, -2))->toBeTrue();
});

test('a full row clears and everything above it falls exactly one row', function () {
    $board = StackBoard::empty(8, 16);

    // A marker high up, then a row that only needs one more cell.
    $board = $board->lock([[0, 0]], 3, 10, '#111111');
    $board = boardWithRow($board, 15, 0);

    [$afterLock] = [$board->lock([[0, 0]], 0, 15, '#222222')];
    [$cleared, $rows] = $afterLock->clearFullRows();

    expect($rows)->toBe(1)
        ->and($cleared->isFilled(3, 10))->toBeFalse('The marker did not fall.')
        ->and($cleared->isFilled(3, 11))->toBeTrue('The marker fell the wrong distance.')
        ->and($cleared->isFilled(0, 15))->toBeFalse('The cleared row is still there.');
});

test('clearing several rows at once drops the stack by all of them', function () {
    $board = StackBoard::empty(8, 16);
    $board = $board->lock([[0, 0]], 3, 10, '#111111');

    foreach ([13, 14, 15] as $row) {
        $board = boardWithRow($board, $row, 0);
        $board = $board->lock([[0, 0]], 0, $row, '#222222');
    }

    [$cleared, $rows] = $board->clearFullRows();

    expect($rows)->toBe(3)
        ->and($cleared->isFilled(3, 13))->toBeTrue('The stack fell the wrong distance.');
});

test('a hole is an empty cell with something above it', function () {
    $board = StackBoard::empty(8, 16);

    expect($board->holeCount())->toBe(0);

    // One cell floating with a gap beneath it: two covered empties below.
    $board = $board->lock([[0, 0]], 2, 13, '#FFFFFF');

    expect($board->holeCount())->toBe(2);

    // Filling directly under it removes one.
    expect($board->lock([[0, 0]], 2, 14, '#FFFFFF')->holeCount())->toBe(1);
});

test('the piece sequence deals whole shuffled bags', function () {
    // The property that makes the game fair: within any seven consecutive
    // pieces from a bag boundary you get each piece exactly once, so nobody
    // waits forever for an I and nobody gets four S pieces running.
    $sequence = (new StackGenerator)->generate(new GameLevel, 'stack-seed', 70);

    expect($sequence)->toHaveCount(70);

    foreach (array_chunk($sequence, 7) as $index => $bag) {
        expect(array_unique($bag))->toHaveCount(7, "Bag {$index} repeats a piece.");
    }
});

test('the piece sequence is deterministic for a seed', function () {
    $generator = new StackGenerator;

    expect($generator->generate(new GameLevel, 'same', 30))->toBe($generator->generate(new GameLevel, 'same', 30))
        ->and($generator->generate(new GameLevel, 'other', 30))->not->toBe($generator->generate(new GameLevel, 'same', 30));
});
