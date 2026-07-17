<?php

namespace App\Domain\Achievements;

use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AchievementService
{
    /**
     * Evaluate active local definitions and persist new unlocks idempotently.
     *
     * @return Collection<int, AchievementUnlock>
     */
    public function evaluate(
        Profile $profile,
        ?GameSession $gameSession = null,
        ?DailyWorkout $dailyWorkout = null,
    ): Collection {
        if ($gameSession !== null && $gameSession->profile_id !== $profile->getKey()) {
            throw new LogicException('Achievement session evidence must belong to the same profile.');
        }

        if ($dailyWorkout !== null && $dailyWorkout->profile_id !== $profile->getKey()) {
            throw new LogicException('Achievement workout evidence must belong to the same profile.');
        }

        return DB::transaction(function () use ($profile, $gameSession, $dailyWorkout): Collection {
            $statistics = Statistic::query()
                ->whereBelongsTo($profile)
                ->get()
                ->keyBy('scope_key');
            $unlockedAchievementIds = AchievementUnlock::query()
                ->whereBelongsTo($profile)
                ->pluck('achievement_id');
            $newUnlocks = collect();

            Achievement::query()
                ->active()
                ->whereNotIn('id', $unlockedAchievementIds)
                ->with('game')
                ->orderBy('sort_order')
                ->get()
                ->each(function (Achievement $achievement) use (
                    $profile,
                    $gameSession,
                    $dailyWorkout,
                    $statistics,
                    $newUnlocks,
                ): void {
                    $evidence = $this->matchingEvidence($achievement, $profile, $statistics);

                    if ($evidence === null) {
                        return;
                    }

                    $unlock = AchievementUnlock::query()->firstOrCreate(
                        [
                            'profile_id' => $profile->getKey(),
                            'achievement_id' => $achievement->getKey(),
                        ],
                        [
                            'game_session_id' => $gameSession?->getKey(),
                            'daily_workout_id' => $dailyWorkout?->getKey(),
                            'unlocked_at' => now(),
                            'evidence' => $evidence,
                        ],
                    );

                    if ($unlock->wasRecentlyCreated) {
                        $newUnlocks->push($unlock);
                    }
                });

            return $newUnlocks;
        });
    }

    /**
     * Return the latest unlocked achievement for a lightweight preview.
     */
    public function latestUnlock(Profile $profile): ?AchievementUnlock
    {
        return AchievementUnlock::query()
            ->whereBelongsTo($profile)
            ->with('achievement.game')
            ->latest('unlocked_at')
            ->latest('id')
            ->first();
    }

    /**
     * @param  Collection<string, Statistic>  $statistics
     * @return array<string, int|float|string>|null
     */
    private function matchingEvidence(
        Achievement $achievement,
        Profile $profile,
        Collection $statistics,
    ): ?array {
        $scopeKey = $achievement->game === null
            ? 'overall'
            : 'game:'.$achievement->game->type->value;
        $statistic = $statistics->get($scopeKey);

        return match ($achievement->type) {
            AchievementType::FirstWorkout => $this->thresholdEvidence(
                'workouts_completed',
                $statistic?->workouts_completed,
                (int) data_get($achievement->criterion, 'workouts', 1),
            ),
            AchievementType::WorkoutStreak => $this->thresholdEvidence(
                'current_streak',
                $statistic?->current_streak,
                (int) data_get($achievement->criterion, 'days', 1),
            ),
            AchievementType::Accuracy => $this->thresholdEvidence(
                'accuracy',
                $statistic?->accuracy,
                (float) data_get($achievement->criterion, 'accuracy', 100),
            ),
            AchievementType::Score => $this->thresholdEvidence(
                'best_score',
                $statistic?->best_score,
                (int) data_get($achievement->criterion, 'score', PHP_INT_MAX),
            ),
            AchievementType::Combo => $this->thresholdEvidence(
                'longest_combo',
                $statistic?->longest_combo,
                (int) data_get($achievement->criterion, 'combo', PHP_INT_MAX),
            ),
            AchievementType::HintFree => $this->hintFreeEvidence($achievement, $profile),
        };
    }

    /**
     * @return array<string, int|float|string>|null
     */
    private function thresholdEvidence(
        string $metric,
        int|float|null $value,
        int|float $threshold,
    ): ?array {
        if ($value === null || $value < $threshold) {
            return null;
        }

        return [
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
        ];
    }

    /**
     * @return array<string, int|float|string>|null
     */
    private function hintFreeEvidence(Achievement $achievement, Profile $profile): ?array
    {
        if ($achievement->game_id === null) {
            return null;
        }

        $minimumCorrect = (int) data_get($achievement->criterion, 'minimum_correct', 1);
        $session = GameSession::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->where('game_id', $achievement->game_id)
            ->where('hint_count', 0)
            ->where('correct_count', '>=', $minimumCorrect)
            ->latest('completed_at')
            ->first();

        if ($session === null) {
            return null;
        }

        return [
            'metric' => 'hint_free_correct',
            'value' => $session->correct_count,
            'threshold' => $minimumCorrect,
        ];
    }
}
