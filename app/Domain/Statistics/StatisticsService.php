<?php

namespace App\Domain\Statistics;

use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
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
     * Add one completed session to overall and per-game aggregates exactly once.
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

            if ($lockedSession->isFrameworkPlaceholder()) {
                throw new LogicException('Framework placeholders cannot update gameplay statistics.');
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

            $lockedSession->update(['statistics_recorded_at' => now()]);
        });
    }

    /**
     * Add a completed daily workout and recalculate streaks exactly once.
     */
    public function recordWorkoutCompletion(DailyWorkout $dailyWorkout): Statistic
    {
        return DB::transaction(function () use ($dailyWorkout): Statistic {
            $lockedWorkout = DailyWorkout::query()
                ->lockForUpdate()
                ->findOrFail($dailyWorkout->getKey());

            if ($lockedWorkout->status !== WorkoutStatus::Completed) {
                throw new LogicException('Only completed workouts may update statistics.');
            }

            $statistic = $this->lockedStatistic($lockedWorkout->profile, 'overall');

            if ($lockedWorkout->statistics_recorded_at !== null) {
                return $statistic;
            }

            [$currentStreak, $longestStreak] = $this->calculateStreaks($lockedWorkout->profile);

            $statistic->update([
                'workouts_completed' => $statistic->workouts_completed + 1,
                'training_seconds' => $statistic->training_seconds + $lockedWorkout->training_seconds,
                'current_streak' => $currentStreak,
                'longest_streak' => max($statistic->longest_streak, $longestStreak),
                'last_workout_date' => $lockedWorkout->workout_date,
                'last_calculated_at' => now(),
            ]);
            $lockedWorkout->update(['statistics_recorded_at' => now()]);

            return $statistic->refresh();
        });
    }

    /**
     * Build a truthful workout summary from its completed session evidence.
     *
     * @return array<string, bool|int|float|null>
     */
    public function dailySummary(DailyWorkout $dailyWorkout): array
    {
        $sessions = GameSession::query()
            ->completed()
            ->whereHas('workoutItem', fn ($query) => $query->whereBelongsTo($dailyWorkout, 'workout'))
            ->with('rounds')
            ->get();
        $evidenceSessions = $sessions->reject(
            fn (GameSession $session): bool => $session->isFrameworkPlaceholder(),
        );
        $correctCount = (int) $evidenceSessions->sum('correct_count');
        $attemptedCount = (int) $evidenceSessions->sum(
            fn (GameSession $session): int => $session->correct_count
                + $session->incorrect_count
                + $session->missed_count,
        );
        $rounds = $evidenceSessions->flatMap->rounds;
        $trainingSeconds = (int) $sessions->sum(function (GameSession $session): int {
            if ($session->isFrameworkPlaceholder()) {
                return max(0, (int) data_get($session->state_snapshot, 'elapsed_seconds', 0));
            }

            if ($session->completed_at === null) {
                return 0;
            }

            return max(0, (int) $session->started_at->diffInSeconds($session->completed_at));
        });

        return [
            'sessions_completed' => $sessions->count(),
            'score' => $evidenceSessions->isEmpty()
                ? null
                : (int) $evidenceSessions->sum('score'),
            'accuracy' => $this->calculateAccuracy($correctCount, $attemptedCount),
            'average_response_ms' => $this->calculateAverageResponseTime($rounds),
            'longest_combo' => $evidenceSessions->isEmpty()
                ? null
                : (int) ($evidenceSessions->max('best_combo') ?? 0),
            'training_seconds' => $trainingSeconds,
            'has_gameplay_evidence' => $evidenceSessions->isNotEmpty(),
        ];
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
            ->withGameplayEvidence()
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
                ->withGameplayEvidence()
                ->update(['statistics_recorded_at' => null]);
            DailyWorkout::query()
                ->whereBelongsTo($profile)
                ->completed()
                ->get()
                ->filter(fn (DailyWorkout $workout): bool => (bool) data_get(
                    $workout->summary,
                    'has_gameplay_evidence',
                    true,
                ))
                ->each->update(['statistics_recorded_at' => null]);
        });

        GameSession::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->withGameplayEvidence()
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

        DailyWorkout::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->oldest('workout_date')
            ->get()
            ->filter(fn (DailyWorkout $workout): bool => (bool) data_get(
                $workout->summary,
                'has_gameplay_evidence',
                true,
            ))
            ->each(fn (DailyWorkout $workout): Statistic => $this->recordWorkoutCompletion($workout));

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
     * @return array{0: int, 1: int}
     */
    private function calculateStreaks(Profile $profile): array
    {
        $dates = DailyWorkout::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->oldest('workout_date')
            ->pluck('workout_date')
            ->map(fn (string $date) => date_create_immutable($date))
            ->filter()
            ->values();
        $current = 0;
        $longest = 0;
        $previous = null;

        foreach ($dates as $date) {
            $current = $previous !== null && $previous->diff($date)->days === 1
                ? $current + 1
                : 1;
            $longest = max($longest, $current);
            $previous = $date;
        }

        return [$current, $longest];
    }
}
