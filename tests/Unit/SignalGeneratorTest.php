<?php

use App\Domain\Games\Signal\SignalGenerator;
use App\Models\GameLevel;

beforeEach(function () {
    $this->generator = new SignalGenerator;
});

function signalLevel(array $configuration = [], int $roundCount = 12): GameLevel
{
    return new GameLevel([
        'round_count' => $roundCount,
        'configuration' => [
            'colors' => ['red', 'blue', 'green', 'purple'],
            'rules' => ['ink', 'word'],
            'options_count' => 4,
            ...$configuration,
        ],
    ]);
}

test('the word and its ink are never the same color', function () {
    $rounds = $this->generator->generate(signalLevel(), 'interference', 40);

    expect($rounds)->toHaveCount(40);

    foreach ($rounds as $round) {
        expect($round['ink'])->not->toBe($round['word']);
    }
});

test('the answer follows the round rule', function () {
    $rounds = $this->generator->generate(signalLevel(), 'rules', 40);

    foreach ($rounds as $round) {
        expect($round['answer'])->toBe($round['rule'] === 'ink' ? $round['ink'] : $round['word']);
    }
});

test('the options always contain the answer and the competing color', function () {
    $rounds = $this->generator->generate(signalLevel(), 'options', 30);

    foreach ($rounds as $round) {
        $decoy = $round['rule'] === 'ink' ? $round['word'] : $round['ink'];

        expect($round['options'])->toHaveCount(4)
            ->and($round['options'])->toContain($round['answer'])
            ->and($round['options'])->toContain($decoy)
            ->and($round['options'])->toBe(array_values(array_unique($round['options'])));
    }
});

test('the options never exceed the level color pool', function () {
    $rounds = $this->generator->generate(
        signalLevel(['colors' => ['red', 'blue', 'green'], 'options_count' => 4]),
        'narrow-pool',
        12,
    );

    foreach ($rounds as $round) {
        expect($round['options'])->toHaveCount(3);

        foreach ($round['options'] as $option) {
            expect($option)->toBeIn(['red', 'blue', 'green']);
        }
    }
});

test('the rule never holds for three rounds in a row', function () {
    $rounds = $this->generator->generate(signalLevel(), 'switching-seed', 60);

    $rules = array_column($rounds, 'rule');

    for ($index = 2; $index < count($rules); $index++) {
        $threeSame = $rules[$index] === $rules[$index - 1]
            && $rules[$index - 1] === $rules[$index - 2];

        expect($threeSame)->toBeFalse();
    }
});

test('a single-rule level stays on that rule', function () {
    $rounds = $this->generator->generate(signalLevel(['rules' => ['ink']]), 'beginner', 20);

    foreach ($rounds as $round) {
        expect($round['rule'])->toBe('ink');
    }
});

test('an empty or invalid configuration falls back to safe defaults', function () {
    $rounds = $this->generator->generate(
        signalLevel(['colors' => ['chartreuse'], 'rules' => ['vibes']]),
        'garbage',
        10,
    );

    foreach ($rounds as $round) {
        expect($round['rule'])->toBe('ink')
            ->and($round['word'])->toBeIn(['red', 'blue', 'green'])
            ->and($round['ink'])->toBeIn(['red', 'blue', 'green']);
    }
});

test('generation is deterministic for a seed', function () {
    expect($this->generator->generate(signalLevel(), 'abc', 12))
        ->toBe($this->generator->generate(signalLevel(), 'abc', 12));
});
