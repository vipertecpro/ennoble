<?php

use App\Domain\Games\QuickMath\QuickMathExplainer;

beforeEach(function () {
    $this->explainer = new QuickMathExplainer;
});

test('it explains multiplication with a repeated-addition chain for small multipliers', function () {
    $steps = $this->explainer->explain('7 × 3', 21);

    expect($steps)->toBeArray()
        ->and($steps)->not->toBeEmpty()
        ->and($steps)->toContain('7 + 7 + 7 = 21.')
        ->and(end($steps))->toBe('So 7 × 3 = 21.');
});

test('it explains addition, subtraction and division', function () {
    $add = $this->explainer->explain('8 + 4', 12);
    $sub = $this->explainer->explain('12 − 5', 7);
    $div = $this->explainer->explain('12 ÷ 3', 4);

    expect($add)->not->toBeEmpty()
        ->and($sub)->not->toBeEmpty()
        ->and($div)->not->toBeEmpty()
        ->and(end($add))->toBe('So 8 + 4 = 12.')
        ->and(end($sub))->toBe('So 12 − 5 = 7.')
        ->and(end($div))->toBe('So 12 ÷ 3 = 4.');
});

test('it falls back to a plain answer for an unparseable expression', function () {
    expect($this->explainer->explain('not a sum', 9))->toBe(['The answer is 9.']);
});

test('every step is a non-empty string', function () {
    foreach ($this->explainer->explain('9 × 9', 81) as $step) {
        expect($step)->toBeString()->not->toBe('');
    }
});
