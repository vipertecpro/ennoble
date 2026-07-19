<?php

namespace App\Domain\Games\WordMatch;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Word Match runtime service. Selects a deterministic round order from the
 * bundled {@see WordBank}, builds each round's shuffled options, and records
 * authoritative round evidence through the shared {@see GameSessionService}.
 * No content table is used — the chosen entry is stored on each GameRound's
 * `response`, mirroring Signal Shift's rule-engine approach.
 */
final class WordMatchGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly WordBank $wordBank,
        private readonly WordMatchScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic list of prepared rounds. Each round
     * carries the prompt word, the relation to match, the correct answer, and
     * the shuffled option order.
     *
     * @return list<array{prompt: string, relation: string, answer: string, options: list<string>}>
     */
    public function roundsFor(GameSession $session): array
    {
        $this->guardSession($session);

        $entries = $this->wordBank->forDifficulty($session->level->difficulty);

        if ($entries === []) {
            throw new LogicException('No bundled Word Match entries exist for this level.');
        }

        $roundCount = max(1, (int) $session->level->round_count);
        $seed = 'word-match:'.$session->getKey();
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->withGameplayEvidence()
            ->count();

        $ordered = $this->seededOrder($entries, $seed);
        $available = count($ordered);
        $rounds = [];

        for ($index = 0; $index < $roundCount; $index++) {
            $entry = $ordered[($rotation + $index) % $available];
            $rounds[] = [
                'prompt' => $entry['prompt'],
                'relation' => $entry['relation'],
                'answer' => $entry['answer'],
                'options' => $this->seededOrder(
                    [$entry['answer'], ...$entry['distractors']],
                    $seed.':round:'.$index,
                ),
            ];
        }

        return $rounds;
    }

    /**
     * Persist one answered round with its authoritative evidence.
     *
     * @param  array{prompt: string, relation: string, answer: string, options: list<string>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        array $round,
        string $chosen,
        int $responseMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $correct = $chosen === $round['answer'];
        $outcome = $correct ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $correct ? max(0, $combo) : 0;
        $boundedResponseMs = max(1, min($responseMs, 300000));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, $boundedResponseMs, $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'prompt' => $round['prompt'],
                    'relation' => $round['relation'],
                    'answer' => $round['answer'],
                    'chosen' => $chosen,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist a timed-out round as an honest miss.
     *
     * @param  array{prompt: string, relation: string, answer: string, options: list<string>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordTimeout(GameSession $session, array $round, array $stateSnapshot): GameRound
    {
        $this->guardSession($session);

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => RoundOutcome::Missed,
                'response_ms' => null,
                'score_delta' => 0,
                'combo' => 0,
                'response' => [
                    'prompt' => $round['prompt'],
                    'relation' => $round['relation'],
                    'answer' => $round['answer'],
                    'chosen' => null,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    public function score(GameSession $session): ScoringResult
    {
        $this->guardSession($session);

        return $this->scoring->score($session->rounds()->get());
    }

    public function previousBestScore(GameSession $session): ?int
    {
        $this->guardSession($session);

        return Statistic::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->value('best_score');
    }

    public function complete(GameSession $session): ScoringResult
    {
        $this->guardSession($session);

        return $this->sessions->complete($session);
    }

    private function guardSession(GameSession $session): void
    {
        $session->loadMissing(['game', 'level', 'profile']);

        if ($session->game->type !== GameType::WordMatch || $session->isFrameworkPlaceholder()) {
            throw new LogicException('Word Match gameplay requires a real Word Match session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $combo): int
    {
        return match ($outcome) {
            RoundOutcome::Correct => 100
                + max(0, 100 - intdiv($responseMs, 40))
                + min($combo * 10, 120),
            RoundOutcome::Incorrect, RoundOutcome::Missed => 0,
        };
    }

    /**
     * Deterministically permute a list by a string seed so round order and
     * option order are stable for a given session but varied across seeds.
     *
     * @template TValue
     *
     * @param  list<TValue>  $items
     * @return list<TValue>
     */
    private function seededOrder(array $items, string $seed): array
    {
        $keyed = [];

        foreach (array_values($items) as $index => $item) {
            $keyed[] = ['key' => hash('sha1', $seed.'#'.$index), 'value' => $item];
        }

        usort($keyed, fn (array $a, array $b): int => strcmp($a['key'], $b['key']));

        return array_map(fn (array $entry) => $entry['value'], $keyed);
    }
}
