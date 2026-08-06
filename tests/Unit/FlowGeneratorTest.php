<?php

use App\Domain\Games\Flow\FlowGenerator;
use App\Models\GameLevel;

beforeEach(function () {
    $this->generator = new FlowGenerator;
});

function flowLevel(array $configuration = [], int $roundCount = 12): GameLevel
{
    return new GameLevel([
        'round_count' => $roundCount,
        'configuration' => ['directions' => ['left', 'right'], 'window_ms' => 2400, ...$configuration],
    ]);
}

test('every current is drawn from the level direction pool', function () {
    $rounds = $this->generator->generate(
        flowLevel(['directions' => ['left', 'right', 'up']]),
        'seed',
        20,
    );

    expect($rounds)->toHaveCount(20);

    foreach ($rounds as $round) {
        expect($round['direction'])->toBeIn(['left', 'right', 'up']);
    }
});

test('no direction surges three times in a row', function () {
    $rounds = $this->generator->generate(
        flowLevel(['directions' => ['left', 'right', 'up', 'down']]),
        'streaky-seed',
        40,
    );

    $directions = array_column($rounds, 'direction');

    for ($index = 2; $index < count($directions); $index++) {
        $threeSame = $directions[$index] === $directions[$index - 1]
            && $directions[$index - 1] === $directions[$index - 2];

        expect($threeSame)->toBeFalse();
    }
});

test('an empty or invalid direction pool falls back to left and right', function () {
    $rounds = $this->generator->generate(
        flowLevel(['directions' => ['sideways', 'nowhere']]),
        'seed',
        10,
    );

    foreach ($rounds as $round) {
        expect($round['direction'])->toBeIn(['left', 'right']);
    }
});

test('generation is deterministic for a seed', function () {
    expect($this->generator->generate(flowLevel(), 'abc', 12))
        ->toBe($this->generator->generate(flowLevel(), 'abc', 12));
});
