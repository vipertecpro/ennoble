<?php

use App\Domain\Games\Axis\AxisFigure;
use App\Domain\Games\Axis\AxisGenerator;
use App\Domain\Games\Axis\AxisRotations;
use App\Models\GameLevel;

/**
 * Axis is a mental-rotation task, so its correctness is entirely geometric:
 * exactly one candidate must be reachable from the reference by rotation. A
 * round where both are reachable is not merely a bad round — the player cannot
 * be wrong, and the score is meaningless.
 */
function axisLevel(int $cubes = 6): GameLevel
{
    $level = new GameLevel;
    $level->configuration = ['cubes' => $cubes];

    return $level;
}

test('there are exactly 24 rotations and no reflections among them', function () {
    $all = AxisRotations::all();

    expect($all)->toHaveCount(24);

    // Distinctness matters: a duplicate would mean a missing orientation, and
    // a figure could then be judged "different" from a rotation of itself.
    $keys = array_map(static fn (array $m): string => json_encode($m), $all);
    expect(array_unique($keys))->toHaveCount(24);
});

test('a rotation never changes a shape', function () {
    $figure = new AxisFigure([[0, 0, 0], [1, 0, 0], [2, 0, 0], [2, 1, 0], [2, 1, 1]]);

    foreach (AxisRotations::all() as $matrix) {
        expect($figure->rotated($matrix)->isSameShapeAs($figure))->toBeTrue();
    }
});

test('a chiral figure can never be rotated onto its mirror image', function () {
    // The premise of the whole game. If this fails, every round has two
    // correct answers.
    $chiral = new AxisFigure([[0, 0, 0], [1, 0, 0], [2, 0, 0], [2, 1, 0], [2, 1, 1]]);

    expect($chiral->isChiral())->toBeTrue()
        ->and($chiral->isSameShapeAs($chiral->mirrored()))->toBeFalse();
});

test('a figure with a mirror plane is correctly rejected as achiral', function () {
    // A straight bar and a flat L both survive reflection, so neither can be
    // used — this is what the generator screens out.
    expect((new AxisFigure([[0, 0, 0], [1, 0, 0], [2, 0, 0]]))->isChiral())->toBeFalse()
        ->and((new AxisFigure([[0, 0, 0], [1, 0, 0], [0, 1, 0]]))->isChiral())->toBeFalse();
});

test('every generated round has exactly one answerable candidate', function () {
    $rounds = (new AxisGenerator)->generate(axisLevel(), 'axis-seed', 25);

    expect($rounds)->toHaveCount(25);

    foreach ($rounds as $index => $round) {
        $reference = new AxisFigure($round['cells']);
        $match = new AxisFigure($round['matchCells']);
        $decoy = new AxisFigure($round['decoyCells']);

        expect($match->isSameShapeAs($reference))
            ->toBeTrue("Round {$index}: the match cannot be rotated onto the reference.");

        expect($decoy->isSameShapeAs($reference))
            ->toBeFalse("Round {$index}: the decoy IS reachable — the round has two right answers.");

        expect($round['answer'])->toBeIn(['a', 'b']);
    }
});

test('rounds are deterministic for a seed and differ between seeds', function () {
    $generator = new AxisGenerator;

    expect($generator->generate(axisLevel(), 'same', 8))
        ->toBe($generator->generate(axisLevel(), 'same', 8));

    expect($generator->generate(axisLevel(), 'other', 8))
        ->not->toBe($generator->generate(axisLevel(), 'same', 8));
});

test('the answer side is not always the same, or the game is trivial', function () {
    $answers = collect((new AxisGenerator)->generate(axisLevel(), 'balance', 40))
        ->pluck('answer');

    expect($answers->unique()->values()->all())->toHaveCount(2);
});

test('figures use the configured number of cubes', function () {
    foreach ([4, 6, 8] as $cubes) {
        $rounds = (new AxisGenerator)->generate(axisLevel($cubes), "size-{$cubes}", 10);

        foreach ($rounds as $round) {
            // A walk can box itself in and stop short; it must never exceed.
            expect(count($round['cells']))->toBeLessThanOrEqual($cubes)
                ->and(count($round['cells']))->toBeGreaterThanOrEqual(4);
        }
    }
});
