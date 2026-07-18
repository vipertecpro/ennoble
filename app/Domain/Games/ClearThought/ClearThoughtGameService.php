<?php

namespace App\Domain\Games\ClearThought;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\Challenge;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use Illuminate\Support\Collection;
use LogicException;

final class ClearThoughtGameService
{
    /**
     * Create the focused Clear Thought runtime service.
     */
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly ClearThoughtScoringService $scoring,
        private readonly ClearThoughtAnswerValidator $validator,
    ) {}

    /**
     * Select this session's deterministic bundled challenge order.
     *
     * @return Collection<int, Challenge>
     */
    public function challengesFor(GameSession $session): Collection
    {
        $this->guardSession($session);

        $challenges = Challenge::query()
            ->active()
            ->where('game_id', $session->game_id)
            ->where('game_level_id', $session->game_level_id)
            ->orderBy('slug')
            ->get();

        if ($challenges->isEmpty()) {
            throw new LogicException('No bundled Clear Thought challenges exist for this level.');
        }

        $roundCount = max(1, (int) $session->level->round_count);
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->withGameplayEvidence()
            ->count() % $challenges->count();

        return $challenges
            ->slice($rotation)
            ->concat($challenges->take($rotation))
            ->take(min($roundCount, $challenges->count()))
            ->values();
    }

    /**
     * Validate a response against bundled accepted answers only.
     *
     * @param  array<string, mixed>  $response
     */
    public function isCorrect(Challenge $challenge, array $response): bool
    {
        return $this->validator->isCorrect($challenge, $response);
    }

    /**
     * Persist one answered challenge with its authoritative attempt evidence.
     *
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordAnswer(
        GameSession $session,
        Challenge $challenge,
        bool $correct,
        int $responseMs,
        int $attempts,
        bool $hintUsed,
        array $response,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);
        $outcome = $correct ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $boundedResponseMs = max(1, min($responseMs, 300000));
        $boundedAttempts = max(1, $attempts);

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'challenge_id' => $challenge->getKey(),
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $this->scoreDelta($outcome, $boundedResponseMs, $boundedAttempts, $hintUsed),
                'combo' => 0,
                'hint_used' => $hintUsed,
                'response' => [
                    'mode' => $challenge->mode->value,
                    'attempts' => $boundedAttempts,
                    ...$response,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Return live score metrics from authoritative persisted evidence.
     */
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

        if ($session->game->type !== GameType::ClearThought || $session->isFrameworkPlaceholder()) {
            throw new LogicException('Clear Thought gameplay requires a real Clear Thought session.');
        }
    }

    private function scoreDelta(
        RoundOutcome $outcome,
        int $responseMs,
        int $attempts,
        bool $hintUsed,
    ): int {
        return match ($outcome) {
            RoundOutcome::Correct => max(0, 200
                + max(0, 100 - intdiv($responseMs, 50))
                - ($hintUsed ? 50 : 0)
                - (($attempts - 1) * 25)),
            RoundOutcome::Incorrect => -25,
            RoundOutcome::Missed => 0,
        };
    }
}
