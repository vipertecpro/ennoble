<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Workout\WorkoutService;
use App\Enums\ClearThoughtMode;
use App\Enums\GameType;
use App\Models\Challenge;
use App\Models\DailyWorkoutItem;
use App\Models\GameSession;
use App\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Native\Mobile\Testing\TestableComponent;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Shared Clear Thought gameplay helpers
|--------------------------------------------------------------------------
*/

function preparedClearThoughtSession(Profile $profile): GameSession
{
    $workout = app(WorkoutService::class)->generateToday($profile);
    $item = $workout->items->firstOrFail(
        fn (DailyWorkoutItem $item): bool => $item->game->type === GameType::ClearThought,
    );
    $session = app(GameSessionService::class)->startForWorkoutItem($profile, $item);

    return app(GameSessionService::class)->checkpoint($session, ['prepared' => true]);
}

function currentClearThoughtChallenge(TestableComponent $game): Challenge
{
    $challengeIds = $game->get('challengeIds');
    $roundNumber = $game->get('roundNumber');

    return Challenge::query()->findOrFail($challengeIds[$roundNumber - 1]);
}

function answerClearThoughtCorrectly(TestableComponent $game): void
{
    $challenge = currentClearThoughtChallenge($game);
    $accepted = $challenge->accepted_answers;

    match ($challenge->mode) {
        ClearThoughtMode::ChooseClearestSentence => $game->call('chooseOption', (string) $accepted[0]),
        ClearThoughtMode::RemoveUnnecessaryWords => (function () use ($game, $accepted): void {
            foreach ($accepted[0] as $wordId) {
                $game->call('toggleWord', (string) $wordId);
            }

            $game->call('submitWords');
        })(),
        ClearThoughtMode::ReorderSentence => (function () use ($game, $accepted): void {
            foreach ($accepted[0] as $segmentId) {
                $game->call('tapSegment', (string) $segmentId);
            }

            $game->call('submitOrder');
        })(),
    };
}

function answerClearThoughtIncorrectly(TestableComponent $game): void
{
    $challenge = currentClearThoughtChallenge($game);

    match ($challenge->mode) {
        ClearThoughtMode::ChooseClearestSentence => $game->call(
            'chooseOption',
            (string) collect($game->get('options'))
                ->filter(fn (array $option): bool => $option['state'] !== 'wrong')
                ->pluck('id')
                ->first(fn (string $id): bool => ! in_array($id, array_map(strval(...), $challenge->accepted_answers), true)),
        ),
        ClearThoughtMode::RemoveUnnecessaryWords => (function () use ($game, $challenge): void {
            $game->call('toggleWord', (string) data_get($challenge->payload, 'words.0.id'));
            $game->call('submitWords');
        })(),
        ClearThoughtMode::ReorderSentence => (function () use ($game, $challenge): void {
            foreach (array_reverse(collect(data_get($challenge->payload, 'segments'))->pluck('id')->all()) as $segmentId) {
                $game->call('tapSegment', (string) $segmentId);
            }

            $game->call('submitOrder');
        })(),
    };
}

function finishClearThoughtPerfectly(TestableComponent $game): TestableComponent
{
    if ($game->get('phase') === 'instructions') {
        $game->tap('Begin Clear Thought');
    }

    while ($game->get('phase') !== 'game_result') {
        if ($game->get('phase') === 'challenge') {
            answerClearThoughtCorrectly($game);
        } elseif ($game->get('phase') === 'reflection') {
            $game->call('continueAfterReflection');
        } else {
            throw new RuntimeException('Unexpected Clear Thought phase: '.$game->get('phase'));
        }
    }

    return $game;
}
