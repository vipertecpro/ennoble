<?php

use App\Domain\Games\Recall\RecallGenerator;
use App\Models\GameLevel;

beforeEach(function () {
    $this->generator = new RecallGenerator;
});

function recallLevel(array $configuration = [], int $roundCount = 5): GameLevel
{
    return new GameLevel([
        'round_count' => $roundCount,
        'configuration' => ['tiles' => 9, 'start_length' => 3, ...$configuration],
    ]);
}

test('sequences grow each round within the tile bounds and never repeat immediately', function () {
    $rounds = $this->generator->generate(recallLevel(), 'seed', 5);

    expect($rounds)->toHaveCount(5);

    foreach ($rounds as $index => $round) {
        $sequence = $round['sequence'];

        expect($sequence)->toHaveCount(min(3 + $index, 9));

        foreach ($sequence as $tile) {
            expect($tile)->toBeGreaterThanOrEqual(0)->toBeLessThan(9);
        }

        for ($step = 1; $step < count($sequence); $step++) {
            expect($sequence[$step])->not->toBe($sequence[$step - 1]);
        }
    }
});

test('the length is capped at the tile count', function () {
    $rounds = $this->generator->generate(recallLevel(['tiles' => 6, 'start_length' => 3]), 'seed', 10);

    // start 3, +1 per round, capped at min(tiles, 9) = 6.
    expect(count($rounds[0]['sequence']))->toBe(3)
        ->and(count($rounds[5]['sequence']))->toBe(6)
        ->and(count($rounds[9]['sequence']))->toBe(6);
});

test('generation is deterministic for a seed', function () {
    expect($this->generator->generate(recallLevel(), 'abc', 4))
        ->toBe($this->generator->generate(recallLevel(), 'abc', 4));
});
