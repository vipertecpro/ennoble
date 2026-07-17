<?php

namespace App\Domain\Workout;

use App\Domain\Achievements\AchievementService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Profile;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class WorkoutService
{
    /**
     * Create the workout orchestration service.
     */
    public function __construct(
        private readonly StatisticsService $statisticsService,
        private readonly AchievementService $achievementService,
    ) {}

    /**
     * Generate or retrieve one deterministic two-game workout for a local date.
     */
    public function generateToday(Profile $profile, ?CarbonInterface $date = null): DailyWorkout
    {
        $workoutDate = ($date ?? today())->toDateString();

        return DB::transaction(function () use ($profile, $workoutDate): DailyWorkout {
            $existingWorkout = DailyWorkout::query()
                ->whereBelongsTo($profile)
                ->forDate($workoutDate)
                ->lockForUpdate()
                ->first();

            if ($existingWorkout !== null) {
                return $existingWorkout->load(['items.game', 'items.level', 'items.sessions']);
            }

            $games = Game::query()
                ->playable()
                ->whereIn('type', [
                    GameType::SignalShift,
                    GameType::ClearThought,
                ])
                ->orderBy('sort_order')
                ->get()
                ->keyBy(fn (Game $game): string => $game->type->value);

            if ($games->count() !== 2) {
                throw new DomainException('Both playable game definitions are required to generate a workout.');
            }

            $workout = DailyWorkout::query()->create([
                'profile_id' => $profile->getKey(),
                'workout_date' => $workoutDate,
                'status' => WorkoutStatus::Pending,
                'generation_version' => 1,
            ]);

            foreach ([GameType::SignalShift, GameType::ClearThought] as $index => $gameType) {
                /** @var Game $game */
                $game = $games->get($gameType->value);
                $levelDifficulty = $profile->difficulty_preference === Difficulty::Adaptive
                    ? Difficulty::Intermediate
                    : $profile->difficulty_preference;
                $level = GameLevel::query()
                    ->whereBelongsTo($game)
                    ->active()
                    ->where('difficulty', $levelDifficulty)
                    ->first();

                if ($level === null) {
                    throw new DomainException("No active {$levelDifficulty->value} level exists for {$game->name}.");
                }

                $workout->items()->create([
                    'game_id' => $game->getKey(),
                    'game_level_id' => $level->getKey(),
                    'position' => $index + 1,
                    'status' => WorkoutStatus::Pending,
                    'configuration' => [
                        'content_version' => 1,
                        'round_count' => $level->round_count,
                        ...$level->configuration,
                    ],
                ]);
            }

            return $workout->load(['items.game', 'items.level', 'items.sessions']);
        });
    }

    /**
     * Retrieve a workout with all state required to resume it.
     */
    public function resume(Profile $profile, ?CarbonInterface $date = null): ?DailyWorkout
    {
        $workoutDate = ($date ?? today())->toDateString();

        return DailyWorkout::query()
            ->whereBelongsTo($profile)
            ->forDate($workoutDate)
            ->with([
                'items.game',
                'items.level',
                'items.sessions' => fn ($query) => $query->latest('started_at'),
            ])
            ->first();
    }

    /**
     * Estimate two configured rounds per minute within the product's 5–10 minute promise.
     */
    public function estimatedDurationMinutes(DailyWorkout $dailyWorkout): int
    {
        $dailyWorkout->loadMissing('items.level');
        $roundCount = (int) $dailyWorkout->items->sum(
            fn ($item): int => $item->level->round_count,
        );

        return max(5, min(10, (int) ceil($roundCount / 2)));
    }

    /**
     * Complete a two-item workout and update summaries, streaks, and achievements.
     */
    public function complete(DailyWorkout $dailyWorkout): DailyWorkout
    {
        return DB::transaction(function () use ($dailyWorkout): DailyWorkout {
            $workout = DailyWorkout::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($dailyWorkout->getKey());

            if ($workout->items->count() !== 2
                || ! $workout->items->every(
                    fn ($item): bool => $item->status === WorkoutStatus::Completed,
                )) {
                throw new LogicException('A daily workout requires both game items to be completed.');
            }

            if ($workout->status !== WorkoutStatus::Completed) {
                $summary = $this->statisticsService->dailySummary($workout);

                $workout->update([
                    'status' => WorkoutStatus::Completed,
                    'started_at' => $workout->started_at ?? $workout->items->min('started_at'),
                    'completed_at' => now(),
                    'training_seconds' => $summary['training_seconds'],
                    'accuracy' => $summary['accuracy'],
                    'summary' => $summary,
                ]);
            }

            $this->statisticsService->recordWorkoutCompletion($workout);
            $this->achievementService->evaluate(
                profile: $workout->profile,
                dailyWorkout: $workout,
            );

            return $workout->refresh()->load(['items.game', 'items.level', 'items.sessions']);
        });
    }

    /**
     * Return recent workout history in newest-first order.
     *
     * @return Collection<int, DailyWorkout>
     */
    public function history(Profile $profile, int $limit = 30): Collection
    {
        return DailyWorkout::query()
            ->whereBelongsTo($profile)
            ->with('items.game')
            ->latest('workout_date')
            ->limit(max(1, $limit))
            ->get();
    }
}
