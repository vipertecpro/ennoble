<?php

namespace App\Domain\Games\QuickMath;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Quick Math runtime service. Generates a deterministic problem set with
 * {@see QuickMathGenerator} and records authoritative round evidence through
 * the shared {@see GameSessionService}. Fully offline — the generated problem
 * is stored on each GameRound's `response`.
 */
final class QuickMathGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly QuickMathGenerator $generator,
        private readonly QuickMathScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic problem set.
     *
     * @return list<array{expression: string, answer: int, options: list<int>}>
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
            seed: 'quick-math:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist one answered problem with its authoritative evidence.
     *
     * @param  array{expression: string, answer: int, options: list<int>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        array $round,
        int $chosen,
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
                    'expression' => $round['expression'],
                    'answer' => $round['answer'],
                    'chosen' => $chosen,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist a timed-out problem as an honest miss.
     *
     * @param  array{expression: string, answer: int, options: list<int>}  $round
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
                    'expression' => $round['expression'],
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

        if ($session->game->type !== GameType::QuickMath) {
            throw new LogicException('Quick Math gameplay requires a real Quick Math session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $combo): int
    {
        return match ($outcome) {
            RoundOutcome::Correct => 100
                + max(0, 120 - intdiv($responseMs, 30))
                + min($combo * 12, 144),
            RoundOutcome::Incorrect, RoundOutcome::Missed => 0,
        };
    }
}
