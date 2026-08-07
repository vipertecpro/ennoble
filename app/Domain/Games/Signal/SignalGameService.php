<?php

namespace App\Domain\Games\Signal;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Signal runtime service. Generates a deterministic stimulus set with
 * {@see SignalGenerator} and records authoritative round evidence through the
 * shared {@see GameSessionService}. Fully offline — the rule in force, the word,
 * the ink, and the player's call are all stored on each GameRound's `response`,
 * which is what {@see SignalScoringService} later scores.
 */
final class SignalGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly SignalGenerator $generator,
        private readonly SignalScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic stimulus set.
     *
     * @return list<array{rule: string, word: string, ink: string, answer: string, options: list<string>}>
     */
    public function roundsFor(GameSession $session): array
    {
        $this->guardSession($session);

        $roundCount = max(1, (int) $session->level->round_count);
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->count();

        return $this->generator->generate(
            level: $session->level,
            seed: 'signal:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist one called round with its authoritative evidence.
     *
     * @param  array{rule: string, word: string, ink: string, answer: string, options: list<string>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        array $round,
        string $chosen,
        int $responseMs,
        int $windowMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $correct = $chosen === $round['answer'];
        $outcome = $correct ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $correct ? max(0, $combo) : 0;
        $boundedResponseMs = max(1, min($responseMs, 300000));
        $boundedWindowMs = max(1, min($windowMs, 300000));
        $switched = $this->isRuleSwitch($session, (string) $round['rule']);

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, $boundedResponseMs, $boundedWindowMs, $storedCombo, $switched),
                'combo' => $storedCombo,
                'response' => [
                    'rule' => (string) $round['rule'],
                    'word' => (string) $round['word'],
                    'ink' => (string) $round['ink'],
                    'answer' => (string) $round['answer'],
                    'chosen' => $chosen,
                    'window_ms' => $boundedWindowMs,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist a round the player never called as an honest miss.
     *
     * @param  array{rule: string, word: string, ink: string, answer: string, options: list<string>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordTimeout(GameSession $session, array $round, int $windowMs, array $stateSnapshot): GameRound
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
                    'rule' => (string) $round['rule'],
                    'word' => (string) $round['word'],
                    'ink' => (string) $round['ink'],
                    'answer' => (string) $round['answer'],
                    'chosen' => null,
                    'window_ms' => max(1, min($windowMs, 300000)),
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

        if ($session->game->type !== GameType::Signal) {
            throw new LogicException('Signal gameplay requires a real Signal session.');
        }
    }

    /**
     * Whether this round's rule flips the one the player last answered under.
     * Read from persisted evidence so the breadcrumb can't drift from the score.
     */
    private function isRuleSwitch(GameSession $session, string $rule): bool
    {
        // `reorder`, not `orderByDesc`: GameSession::rounds() bakes in an
        // ascending `orderBy('round_number')`, so appending a descending order
        // is ignored and `first()` silently returns round ONE for every round.
        $previous = $session->rounds()
            ->reorder('round_number', 'desc')
            ->first();

        if ($previous === null || ! is_array($previous->response)) {
            return false;
        }

        $previousRule = $previous->response['rule'] ?? null;

        return is_string($previousRule) && $previousRule !== '' && $previousRule !== $rule;
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $windowMs, int $combo, bool $switched): int
    {
        if ($outcome !== RoundOutcome::Correct) {
            return 0;
        }

        $fraction = 1 - min(1.0, $responseMs / $windowMs);
        $speedBonus = max(0, min(50, (int) round($fraction * 50)));

        return 100 + $speedBonus + min($combo * 10, 120) + ($switched ? 25 : 0);
    }
}
