<?php

use App\Domain\Games\SignalShift\SignalShiftRule;
use App\Domain\Games\SignalShift\SignalShiftRuleEngine;
use App\Models\GameLevel;

test('a rule combines every configured condition without hard-coded branches', function () {
    $rule = SignalShiftRule::fromArray([
        'target_color' => 'gold',
        'excluded_shape' => 'square',
        'motion_required' => true,
        'size_required' => 'small',
        'rotation_required' => true,
        'speed_modifier' => 1.25,
        'spawn_density' => 5,
        'wave_count' => 3,
        'seconds_per_wave' => 2,
    ]);

    expect($rule->matches([
        'color' => 'gold',
        'shape' => 'circle',
        'moving' => true,
        'size' => 'small',
        'rotated' => true,
    ]))->toBeTrue()
        ->and($rule->matches([
            'color' => 'gold',
            'shape' => 'square',
            'moving' => true,
            'size' => 'small',
            'rotated' => true,
        ]))->toBeFalse()
        ->and($rule->matches([
            'color' => 'gold',
            'shape' => 'circle',
            'moving' => false,
            'size' => 'small',
            'rotated' => true,
        ]))->toBeFalse()
        ->and($rule->instruction())
        ->toBe('Tap gold shapes except squares that are moving that are small that are tilted.')
        ->and($rule->toArray()['speed_modifier'])->toBe(1.25)
        ->and($rule->toArray()['spawn_density'])->toBe(5);
});

test('the engine requires three configured rounds', function () {
    $level = new GameLevel([
        'configuration' => [
            'rounds' => [
                ['target_color' => 'teal'],
            ],
        ],
    ]);

    expect(fn () => app(SignalShiftRuleEngine::class)->rulesFor($level))
        ->toThrow(DomainException::class, 'exactly three');
});

test('wave generation is deterministic and contains exactly one eligible target', function () {
    $engine = app(SignalShiftRuleEngine::class);
    $rule = SignalShiftRule::fromArray([
        'target_shape' => 'square',
        'motion_required' => true,
        'rotation_required' => true,
        'spawn_density' => 6,
    ]);
    $configuration = [
        'palette' => ['teal', 'gold', 'coral'],
        'shapes' => ['circle', 'square', 'diamond'],
    ];

    $first = $engine->stimuliForWave($rule, $configuration, 8123);
    $second = $engine->stimuliForWave($rule, $configuration, 8123);

    expect($first)->toBe($second)
        ->and($first)->toHaveCount(6)
        ->and(collect($first)->where('is_target', true))->toHaveCount(1)
        ->and($rule->matches(collect($first)->firstWhere('is_target', true)))->toBeTrue()
        ->and(collect($first)->where('is_target', false)->every(
            fn (array $stimulus): bool => ! $rule->matches($stimulus),
        ))->toBeTrue();
});

test('invalid rule bounds are rejected', function (array $configuration, string $message) {
    expect(fn () => SignalShiftRule::fromArray($configuration))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'conflicting shape' => [[
        'target_shape' => 'circle',
        'excluded_shape' => 'circle',
    ], 'cannot also be excluded'],
    'unsafe density' => [['spawn_density' => 7], 'between 2 and 6'],
    'unsafe speed' => [['speed_modifier' => 2.5], 'between 0.5 and 2.0'],
    'unsafe timer' => [['seconds_per_wave' => 0], 'between 1 and 10'],
]);
