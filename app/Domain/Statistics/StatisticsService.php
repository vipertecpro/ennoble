<?php

namespace App\Domain\Statistics;

use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\SessionStatus;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class StatisticsService
{
    /**
     * Calculate a percentage, preserving unavailable data as null.
     */
    public function calculateAccuracy(int $correctCount, int $attemptedCount): ?float
    {
        if ($correctCount < 0 || $attemptedCount < 0 || $correctCount > $attemptedCount) {
            throw new InvalidArgumentException('Accuracy counts must be non-negative and internally consistent.');
        }

        if ($attemptedCount === 0) {
            return null;
        }

        return round(($correctCount / $attemptedCount) * 100, 2);
    }

    /**
     * Calculate the mean response time from compatible persisted rounds.
     *
     * @param  Collection<int, GameRound>  $rounds
     */
    public function calculateAverageResponseTime(Collection $rounds): ?int
    {
        $compatibleRounds = $rounds->whereNotNull('response_ms');

        return $compatibleRounds->isEmpty()
            ? null
            : (int) round($compatibleRounds->avg('response_ms'));
    }

    /**
     * Add one completed session to overall and per-game aggregates exactly once,
     * then recalculate the play-day streak from all completed-session evidence.
     */
    public function recordGameSession(GameSession $gameSession, ScoringResult $result): void
    {
        DB::transaction(function () use ($gameSession, $result): void {
            $lockedSession = GameSession::query()
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if ($lockedSession->status !== SessionStatus::Completed) {
                throw new LogicException('Only completed sessions may update statistics.');
            }

            if ($lockedSession->statistics_recorded_at !== null) {
                return;
            }

            $responseQuery = GameRound::query()
                ->whereBelongsTo($lockedSession, 'session')
                ->whereNotNull('response_ms');
            $responseCount = (clone $responseQuery)->count();
            $totalResponseMs = (int) (clone $responseQuery)->sum('response_ms');
            $attemptedCount = $result->correctCount + $result->incorrectCount + $result->missedCount;

            $this->addSessionEvidence(
                profile: $lockedSession->profile,
                scopeKey: 'overall',
                gameId: null,
                result: $result,
                attemptedCount: $attemptedCount,
                responseCount: $responseCount,
                totalResponseMs: $totalResponseMs,
            );
            $this->addSessionEvidence(
                profile: $lockedSession->profile,
                scopeKey: 'game:'.$lockedSession->game->type->value,
                gameId: $lockedSession->game_id,
                result: $result,
                attemptedCount: $attemptedCount,
                responseCount: $responseCount,
                totalResponseMs: $totalResponseMs,
            );

            $this->recordPlayStreak($lockedSession->profile);

            $lockedSession->update(['statistics_recorded_at' => now()]);
        });
    }

    /**
     * Return per-game personal-best aggregates.
     *
     * @return Collection<int, Statistic>
     */
    public function personalBests(Profile $profile): Collection
    {
        return Statistic::query()
            ->whereBelongsTo($profile)
            ->whereNotNull('game_id')
            ->with('game')
            ->orderBy('scope_key')
            ->get();
    }

    /**
     * Return evidence-backed statistics used by the Games library.
     *
     * @return Collection<int, array{
     *     best_score: int|null,
     *     completion_count: int,
     *     completion_rate: int|null,
     *     last_played_at: CarbonInterface|null,
     *     session_count: int
     * }>
     */
    public function gamePreviews(Profile $profile): Collection
    {
        $statistics = $this->personalBests($profile)->keyBy('game_id');
        $sessionSummaries = GameSession::query()
            ->whereBelongsTo($profile)
            ->select('game_id')
            ->selectRaw('COUNT(*) as session_count')
            ->selectRaw('MAX(started_at) as last_played_at')
            ->groupBy('game_id')
            ->get()
            ->keyBy('game_id');
        $gameIds = $statistics->keys()
            ->merge($sessionSummaries->keys())
            ->unique();

        return $gameIds->mapWithKeys(function (int $gameId) use ($statistics, $sessionSummaries): array {
            /** @var Statistic|null $statistic */
            $statistic = $statistics->get($gameId);
            /** @var GameSession|null $sessionSummary */
            $sessionSummary = $sessionSummaries->get($gameId);
            $sessionCount = (int) ($sessionSummary?->getAttribute('session_count') ?? 0);
            $completionCount = $statistic?->sessions_completed ?? 0;
            $lastPlayedAt = $sessionSummary?->getAttribute('last_played_at');

            return [
                $gameId => [
                    'best_score' => $statistic?->best_score,
                    'completion_count' => $completionCount,
                    'completion_rate' => $this->calculateCompletionRate($completionCount, $sessionCount),
                    'last_played_at' => is_string($lastPlayedAt)
                        ? CarbonImmutable::parse($lastPlayedAt)
                        : null,
                    'session_count' => $sessionCount,
                ],
            ];
        });
    }

    /**
     * Return the persisted overall dashboard aggregate when evidence exists.
     */
    public function overview(Profile $profile): ?Statistic
    {
        return Statistic::query()
            ->whereBelongsTo($profile)
            ->overall()
            ->first();
    }

    /**
     * Calculate the percentage of started sessions that were completed.
     */
    public function calculateCompletionRate(int $completedCount, int $sessionCount): ?int
    {
        if ($completedCount < 0 || $sessionCount < 0 || $completedCount > $sessionCount) {
            throw new InvalidArgumentException('Completion counts must be non-negative and internally consistent.');
        }

        if ($sessionCount === 0) {
            return null;
        }

        return (int) round(($completedCount / $sessionCount) * 100);
    }

    /**
     * Rebuild all cached statistics from authoritative completed records.
     *
     * @return Collection<int, Statistic>
     */
    public function rebuild(Profile $profile): Collection
    {
        DB::transaction(function () use ($profile): void {
            Statistic::query()->whereBelongsTo($profile)->delete();
            GameSession::query()
                ->whereBelongsTo($profile)
                ->completed()
                ->update(['statistics_recorded_at' => null]);
        });

        GameSession::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->with('rounds')
            ->oldest('completed_at')
            ->each(function (GameSession $session): void {
                $result = new ScoringResult(
                    score: $session->score ?? 0,
                    accuracy: $session->accuracy,
                    averageResponseMs: $session->average_response_ms,
                    correctCount: $session->correct_count,
                    incorrectCount: $session->incorrect_count,
                    missedCount: $session->missed_count,
                    hintCount: $session->hint_count,
                    bestCombo: $session->best_combo,
                    summary: [],
                );

                $this->recordGameSession($session, $result);
            });

        return Statistic::query()
            ->whereBelongsTo($profile)
            ->orderBy('scope_key')
            ->get();
    }

    private function addSessionEvidence(
        Profile $profile,
        string $scopeKey,
        ?int $gameId,
        ScoringResult $result,
        int $attemptedCount,
        int $responseCount,
        int $totalResponseMs,
    ): void {
        $statistic = $this->lockedStatistic($profile, $scopeKey, $gameId);
        $correctCount = $statistic->correct_count + $result->correctCount;
        $totalAttempted = $statistic->attempted_count + $attemptedCount;
        $combinedResponseCount = $statistic->response_count + $responseCount;
        $combinedResponseMs = $statistic->total_response_ms + $totalResponseMs;

        $statistic->update([
            'sessions_completed' => $statistic->sessions_completed + 1,
            'correct_count' => $correctCount,
            'attempted_count' => $totalAttempted,
            'total_response_ms' => $combinedResponseMs,
            'response_count' => $combinedResponseCount,
            'accuracy' => $this->calculateAccuracy($correctCount, $totalAttempted),
            'average_response_ms' => $combinedResponseCount === 0
                ? null
                : (int) round($combinedResponseMs / $combinedResponseCount),
            'best_score' => max($statistic->best_score ?? 0, $result->score),
            'longest_combo' => max($statistic->longest_combo, $result->bestCombo),
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Recalculate the daily play streak from completed-session evidence and
     * persist it on the overall aggregate.
     */
    private function recordPlayStreak(Profile $profile): void
    {
        [$current, $longest, $lastPlayedDate] = $this->calculateStreaks($profile);
        $statistic = $this->lockedStatistic($profile, 'overall');

        $statistic->update([
            'current_streak' => $current,
            'longest_streak' => max($statistic->longest_streak, $longest),
            'last_played_date' => $lastPlayedDate,
            'last_calculated_at' => now(),
        ]);
    }

    private function lockedStatistic(Profile $profile, string $scopeKey, ?int $gameId = null): Statistic
    {
        Statistic::query()->firstOrCreate(
            ['profile_id' => $profile->getKey(), 'scope_key' => $scopeKey],
            ['game_id' => $gameId],
        );

        return Statistic::query()
            ->whereBelongsTo($profile)
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Derive the current and longest streaks of consecutive days on which the
     * profile completed at least one game, plus the most recent play date.
     *
     * @return array{0: int, 1: int, 2: ?string}
     */
    private function calculateStreaks(Profile $profile): array
    {
        $dates = GameSession::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->whereNotNull('completed_at')
            ->pluck('completed_at')
            ->map(fn ($timestamp): CarbonImmutable => CarbonImmutable::parse($timestamp)->startOfDay())
            ->unique(fn (CarbonImmutable $date): string => $date->toDateString())
            ->sort()
            ->values();
        $current = 0;
        $longest = 0;
        $previous = null;

        foreach ($dates as $date) {
            $current = $previous !== null && $date->equalTo($previous->addDay())
                ? $current + 1
                : 1;
            $longest = max($longest, $current);
            $previous = $date;
        }

        if ($previous !== null && $previous->lt(today()->subDay()->startOfDay())) {
            $current = 0;
        }

        return [$current, $longest, $previous?->toDateString()];
    }
}
