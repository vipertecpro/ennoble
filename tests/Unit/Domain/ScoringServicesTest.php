<?php

use App\Domain\Games\ClearThought\ClearThoughtAnswerValidator;
use App\Domain\Games\ClearThought\ClearThoughtScoringService;
use App\Domain\Games\SignalShift\SignalShiftScoringService;
use App\Enums\ClearThoughtMode;
use App\Enums\RoundOutcome;
use App\Models\Challenge;
use App\Models\GameRound;

test('signal shift rewards accurate responses and penalizes random tapping', function () {
    $accurateRounds = collect([
        new GameRound(['outcome' => RoundOutcome::Correct, 'response_ms' => 800, 'combo' => 1]),
        new GameRound(['outcome' => RoundOutcome::Correct, 'response_ms' => 700, 'combo' => 2]),
        new GameRound(['outcome' => RoundOutcome::Correct, 'response_ms' => 600, 'combo' => 3]),
        new GameRound(['outcome' => RoundOutcome::Incorrect, 'response_ms' => 100, 'combo' => 0]),
    ]);
    $randomRounds = collect([
        new GameRound(['outcome' => RoundOutcome::Incorrect, 'response_ms' => 100, 'combo' => 0]),
        new GameRound(['outcome' => RoundOutcome::Incorrect, 'response_ms' => 100, 'combo' => 0]),
        new GameRound(['outcome' => RoundOutcome::Correct, 'response_ms' => 100, 'combo' => 1]),
        new GameRound(['outcome' => RoundOutcome::Missed, 'response_ms' => null, 'combo' => 0]),
    ]);

    $service = new SignalShiftScoringService;
    $accurate = $service->score($accurateRounds);
    $random = $service->score($randomRounds);

    expect($accurate->accuracy)->toBe(75.0)
        ->and($accurate->averageResponseMs)->toBe(700)
        ->and($accurate->bestCombo)->toBe(3)
        ->and($accurate->score)->toBeGreaterThan($random->score)
        ->and($random->accuracy)->toBe(25.0);
});

test('clear thought applies attempt and hint penalties deterministically', function () {
    $withoutHint = collect([
        new GameRound([
            'outcome' => RoundOutcome::Correct,
            'response_ms' => 1000,
            'hint_used' => false,
            'response' => ['attempts' => 1],
        ]),
    ]);
    $withHint = collect([
        new GameRound([
            'outcome' => RoundOutcome::Correct,
            'response_ms' => 1000,
            'hint_used' => true,
            'response' => ['attempts' => 2],
        ]),
    ]);

    $service = new ClearThoughtScoringService;
    $cleanResult = $service->score($withoutHint);
    $assistedResult = $service->score($withHint);

    expect($cleanResult->accuracy)->toBe(100.0)
        ->and($cleanResult->averageResponseMs)->toBe(1000)
        ->and($cleanResult->score)->toBeGreaterThan($assistedResult->score)
        ->and($assistedResult->hintCount)->toBe(1);
});

test('clear thought validates all bundled answer modes without network evaluation', function (
    ClearThoughtMode $mode,
    array $acceptedAnswers,
    array $response,
    bool $expected,
) {
    $challenge = new Challenge([
        'mode' => $mode,
        'accepted_answers' => $acceptedAnswers,
    ]);

    expect((new ClearThoughtAnswerValidator)->isCorrect($challenge, $response))->toBe($expected);
})->with([
    'remove words ignores selection order' => [
        ClearThoughtMode::RemoveUnnecessaryWords,
        [[1, 3]],
        ['selected' => [3, 1]],
        true,
    ],
    'reorder sentence preserves order' => [
        ClearThoughtMode::ReorderSentence,
        [['Clear', 'ideas', 'travel']],
        ['segments' => ['Clear', 'ideas', 'travel']],
        true,
    ],
    'wrong sentence order fails' => [
        ClearThoughtMode::ReorderSentence,
        [['Clear', 'ideas', 'travel']],
        ['segments' => ['ideas', 'Clear', 'travel']],
        false,
    ],
    'clearest option matches an explicit answer' => [
        ClearThoughtMode::ChooseClearestSentence,
        [2],
        ['option' => 2],
        true,
    ],
]);
