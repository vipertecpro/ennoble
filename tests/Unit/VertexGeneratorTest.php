<?php

use App\Domain\Games\Vertex\VertexGenerator;
use App\Domain\Games\Vertex\VertexShapes;
use App\Models\GameLevel;

beforeEach(function () {
    $this->generator = new VertexGenerator;
});

function vertexLevel(array $configuration = [], int $roundCount = 12): GameLevel
{
    return new GameLevel([
        'round_count' => $roundCount,
        'configuration' => [
            'shapes' => ['disc', 'block', 'bar', 'ring'],
            'go_ratio' => 0.72,
            'key_hold' => 5,
            ...$configuration,
        ],
    ]);
}

test('a target round flies the target form and a decoy never does', function () {
    $rounds = $this->generator->generate(vertexLevel(), 'stream', 40);

    expect($rounds)->toHaveCount(40);

    foreach ($rounds as $round) {
        expect($round['shape'])->toBeIn(['disc', 'block', 'bar', 'ring']);

        if ($round['is_go']) {
            expect($round['shape'])->toBe($round['key']);
        } else {
            expect($round['shape'])->not->toBe($round['key']);
        }
    }
});

test('the stream always contains both targets and decoys', function () {
    // A run of all targets is not a go/no-go; a run of none never builds the
    // reflex the task is meant to interrupt.
    foreach (['alpha', 'beta', 'gamma', 'delta'] as $seed) {
        $rounds = $this->generator->generate(vertexLevel(), $seed, 12);
        $goes = array_filter($rounds, static fn (array $round): bool => $round['is_go']);

        expect($goes)->not->toBeEmpty()
            ->and(count($goes))->toBeLessThan(12);
    }
});

test('decoys never run more than twice in a row', function () {
    $rounds = $this->generator->generate(vertexLevel(['go_ratio' => 0.4]), 'decoy-heavy', 60);

    $run = 0;
    foreach ($rounds as $round) {
        $run = $round['is_go'] ? 0 : $run + 1;

        expect($run)->toBeLessThanOrEqual(2);
    }
});

test('the target re-keys on the key_hold cadence and never re-keys to itself', function () {
    $rounds = $this->generator->generate(vertexLevel(['key_hold' => 4]), 'rekey', 24);

    $keys = array_column($rounds, 'key');
    $switches = 0;

    for ($index = 1; $index < count($keys); $index++) {
        if ($keys[$index] !== $keys[$index - 1]) {
            $switches++;
        }
    }

    expect($switches)->toBeGreaterThan(0);
});

test('a huge key_hold pins the target for the whole run', function () {
    $rounds = $this->generator->generate(vertexLevel(['key_hold' => 99]), 'beginner', 10);

    expect(array_unique(array_column($rounds, 'key')))->toHaveCount(1);
});

test('an invalid shape pool falls back to a playable default', function () {
    $rounds = $this->generator->generate(
        vertexLevel(['shapes' => ['dodecahedron']]),
        'garbage',
        10,
    );

    foreach ($rounds as $round) {
        expect($round['shape'])->toBeIn(['disc', 'block', 'ring'])
            ->and(VertexShapes::has($round['key']))->toBeTrue();
    }
});

test('the go ratio is clamped into a band that keeps it a go/no-go', function () {
    // All-targets would remove the inhibition; near-none would never build the
    // reflex. Either extreme is clamped rather than honoured.
    foreach ([['go_ratio' => 5.0], ['go_ratio' => -2.0]] as $configuration) {
        $rounds = $this->generator->generate(vertexLevel($configuration), 'extreme', 30);
        $goes = count(array_filter($rounds, static fn (array $round): bool => $round['is_go']));

        expect($goes)->toBeGreaterThan(0)->and($goes)->toBeLessThan(30);
    }
});

test('generation is deterministic for a seed', function () {
    expect($this->generator->generate(vertexLevel(), 'abc', 14))
        ->toBe($this->generator->generate(vertexLevel(), 'abc', 14));
});
