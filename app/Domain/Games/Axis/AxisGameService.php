<?php

namespace App\Domain\Games\Axis;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Axis runtime service. Generates a deterministic trial set with
 * {@see AxisGenerator} and records authoritative round evidence through the
 * shared {@see GameSessionService}.
 *
 * The figure's cube count is stored on each round because the score depends on
 * it: a seven-cube solid is harder to rotate mentally than a four-cube one, and
 * {@see AxisScoringService} pays for that. Storing it as evidence rather than
 * re-deriving it from the level means a level's configuration can change later
 * without silently rescoring history.
 */
final class AxisGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly AxisGenerator $generator,
        private readonly AxisScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic trial set.
     *
     * @return list<array{cells: list<array{0: int, 1: int, 2: int}>, matchCells: list<array{0: int, 1: int, 2: int}>, decoyCells: list<array{0: int, 1: int, 2: int}>, answer: string}>
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
            seed: 'axis:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist one called round with its authoritative evidence.
     *
     * @param  array{cells: list<array{0: int, 1: int, 2: int}>, answer: string}  $round
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
        $cubes = count($round['cells']);

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, $boundedResponseMs, $boundedWindowMs, $storedCombo, $cubes),
                'combo' => $storedCombo,
                'response' => [
                    'cubes' => $cubes,
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
     * @param  array{cells: list<array{0: int, 1: int, 2: int}>, answer: string}  $round
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
                    'cubes' => count($round['cells']),
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

        if ($session->game->type !== GameType::Axis) {
            throw new LogicException('Axis gameplay requires a real Axis session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $windowMs, int $combo, int $cubes): int
    {
        if ($outcome !== RoundOutcome::Correct) {
            return 0;
        }

        $fraction = 1 - min(1.0, $responseMs / $windowMs);
        $speedBonus = max(0, min(50, (int) round($fraction * 50)));
        $complexity = max(0, $cubes - 4) * 12;

        return 100 + $speedBonus + min($combo * 10, 120) + $complexity;
    }
}
