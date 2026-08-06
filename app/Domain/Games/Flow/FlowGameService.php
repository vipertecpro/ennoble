<?php

namespace App\Domain\Games\Flow;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Flow runtime service. Generates a deterministic current set with
 * {@see FlowGenerator} and records authoritative round evidence through the
 * shared {@see GameSessionService}. Fully offline — the required direction and
 * the player's swipe are stored on each GameRound's `response`.
 */
final class FlowGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly FlowGenerator $generator,
        private readonly FlowScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic current set.
     *
     * @return list<array{direction: string}>
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
            seed: 'flow:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist one swiped current with its authoritative evidence.
     *
     * @param  array{direction: string}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        array $round,
        string $swiped,
        int $responseMs,
        int $windowMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $direction = (string) $round['direction'];
        $correct = $swiped === $direction;
        $outcome = $correct ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $correct ? max(0, $combo) : 0;
        $boundedResponseMs = max(1, min($responseMs, 300000));
        $boundedWindowMs = max(1, min($windowMs, 300000));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, $boundedResponseMs, $boundedWindowMs, $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'direction' => $direction,
                    'swiped' => $swiped,
                    'window_ms' => $boundedWindowMs,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist a current that reached the player un-swiped as an honest miss.
     *
     * @param  array{direction: string}  $round
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
                    'direction' => (string) $round['direction'],
                    'swiped' => null,
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

        if ($session->game->type !== GameType::Flow) {
            throw new LogicException('Flow gameplay requires a real Flow session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $windowMs, int $combo): int
    {
        if ($outcome !== RoundOutcome::Correct) {
            return 0;
        }

        $fraction = 1 - min(1.0, $responseMs / $windowMs);
        $speedBonus = max(0, min(50, (int) round($fraction * 50)));

        return 100 + $speedBonus + min($combo * 10, 120);
    }
}
