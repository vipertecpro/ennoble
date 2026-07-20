<?php

namespace App\Domain\Games\Recall;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Recall runtime service. Generates a deterministic sequence set with
 * {@see RecallGenerator} and records authoritative round evidence through the
 * shared {@see GameSessionService}. Fully offline — the sequence and the
 * player's reproduction are stored on each GameRound's `response`.
 */
final class RecallGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly RecallGenerator $generator,
        private readonly RecallScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic sequence set.
     *
     * @return list<array{sequence: list<int>}>
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
            seed: 'recall:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist one reproduced sequence with its authoritative evidence.
     *
     * @param  array{sequence: list<int>}  $round
     * @param  list<int>  $entered
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        array $round,
        array $entered,
        int $responseMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $sequence = array_values(array_map('intval', $round['sequence']));
        $normalizedEntered = array_values(array_map('intval', $entered));
        $correct = $normalizedEntered === $sequence;
        $outcome = $correct ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $correct ? max(0, $combo) : 0;
        $boundedResponseMs = max(1, min($responseMs, 300000));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, count($sequence), $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'sequence' => $sequence,
                    'length' => count($sequence),
                    'entered' => $normalizedEntered,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist a skipped sequence as an honest miss.
     *
     * @param  array{sequence: list<int>}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordTimeout(GameSession $session, array $round, array $stateSnapshot): GameRound
    {
        $this->guardSession($session);

        $sequence = array_values(array_map('intval', $round['sequence']));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => RoundOutcome::Missed,
                'response_ms' => null,
                'score_delta' => 0,
                'combo' => 0,
                'response' => [
                    'sequence' => $sequence,
                    'length' => count($sequence),
                    'entered' => [],
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

        if ($session->game->type !== GameType::Recall) {
            throw new LogicException('Recall gameplay requires a real Recall session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $length, int $combo): int
    {
        return match ($outcome) {
            RoundOutcome::Correct => 100 + max(0, ($length - 2) * 20) + min($combo * 12, 144),
            RoundOutcome::Incorrect, RoundOutcome::Missed => 0,
        };
    }
}
