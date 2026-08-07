<?php

use App\Domain\Games\Vertex\VertexGenerator;
use App\Domain\Games\Vertex\VertexVocabulary;
use App\Models\GameLevel;

beforeEach(function () {
    $this->generator = new VertexGenerator;
});

function barrageLevel(array $configuration = [], int $roundCount = 10): GameLevel
{
    return new GameLevel([
        'round_count' => $roundCount,
        'configuration' => [
            'shapes' => ['disc', 'block', 'bar', 'ring'],
            'colours' => ['blue', 'amber', 'violet', 'rose'],
            'forms' => ['shape', 'colour', 'not_shape', 'not_colour', 'both'],
            'formation' => 12,
            ...$configuration,
        ],
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function barrageTargets(array $wave): array
{
    return array_values(array_filter($wave['invaders'], fn (array $i): bool => $i['is_target']));
}

test('every invader is flagged consistently with the wave criterion', function () {
    $waves = $this->generator->generate(barrageLevel(), 'flags', 30);

    foreach ($waves as $wave) {
        foreach ($wave['invaders'] as $invader) {
            expect($invader['is_target'])
                ->toBe(VertexVocabulary::matches($wave['criterion'], $invader));
        }
    }
});

test('every wave holds at least one target and at least one decoy', function () {
    // A wave of all-targets is a tapping drill; a wave of none is dead air.
    foreach (['alpha', 'beta', 'gamma', 'delta', 'epsilon'] as $seed) {
        foreach ($this->generator->generate(barrageLevel(), $seed, 12) as $wave) {
            $targets = count(barrageTargets($wave));

            expect($targets)->toBeGreaterThan(0)
                ->and($targets)->toBeLessThan(count($wave['invaders']));
        }
    }
});

test('targets are never the majority of a formation', function () {
    // Visual search only bites when hits are sparse among distractors —
    // a mostly-target field collapses back into "tap everything".
    foreach ($this->generator->generate(barrageLevel(), 'sparse', 40) as $wave) {
        expect(count(barrageTargets($wave)))
            ->toBeLessThanOrEqual((int) floor(count($wave['invaders']) / 2));
    }
});

test('a conjunction wave still fields near-misses on both attributes', function () {
    // Under "fire at blue rings" the field must contain blue non-rings AND
    // non-blue rings, or one attribute alone would separate it and the
    // conjunction would never have to be held.
    $waves = collect($this->generator->generate(barrageLevel(['forms' => ['both']]), 'conjunction', 24))
        ->filter(fn (array $w): bool => count($w['invaders']) >= 8);

    expect($waves)->not->toBeEmpty();

    $withBothNearMisses = $waves->filter(function (array $wave): bool {
        $shape = $wave['criterion']['shape'];
        $colour = $wave['criterion']['colour'];
        $decoys = array_filter($wave['invaders'], fn (array $i): bool => ! $i['is_target']);

        $sharesColour = array_filter($decoys, fn (array $i): bool => $i['colour'] === $colour);
        $sharesShape = array_filter($decoys, fn (array $i): bool => $i['shape'] === $shape);

        return $sharesColour !== [] && $sharesShape !== [];
    });

    expect($withBothNearMisses->count())->toBeGreaterThan($waves->count() / 2);
});

test('a negated criterion selects everything except the named attribute', function () {
    foreach ($this->generator->generate(barrageLevel(['forms' => ['not_shape']]), 'negation', 12) as $wave) {
        $excluded = $wave['criterion']['shape'];

        foreach ($wave['invaders'] as $invader) {
            expect($invader['is_target'])->toBe($invader['shape'] !== $excluded);
        }
    }
});

test('the order label names what the criterion actually selects', function () {
    $waves = $this->generator->generate(barrageLevel(), 'labels', 20);

    foreach ($waves as $wave) {
        expect($wave['order'])->toBe(VertexVocabulary::orderLabel($wave['criterion']))
            ->and($wave['order'])->not->toBe('Hold fire');
    }
});

test('an invalid vocabulary falls back to a playable default', function () {
    $waves = $this->generator->generate(
        barrageLevel(['shapes' => ['tetrahedron'], 'colours' => ['ultraviolet'], 'forms' => ['vibes']]),
        'garbage',
        6,
    );

    foreach ($waves as $wave) {
        expect($wave['criterion']['type'])->toBe('shape');

        foreach ($wave['invaders'] as $invader) {
            expect(VertexVocabulary::hasShape($invader['shape']))->toBeTrue()
                ->and(VertexVocabulary::hasColour($invader['colour']))->toBeTrue();
        }
    }
});

test('the formation size is clamped into a playable band', function () {
    expect($this->generator->generate(barrageLevel(['formation' => 1]), 'tiny', 3)[0]['invaders'])
        ->toHaveCount(4)
        ->and($this->generator->generate(barrageLevel(['formation' => 99]), 'huge', 3)[0]['invaders'])
        ->toHaveCount(16);
});

test('generation is deterministic for a seed', function () {
    expect($this->generator->generate(barrageLevel(), 'abc', 10))
        ->toBe($this->generator->generate(barrageLevel(), 'abc', 10));
});
