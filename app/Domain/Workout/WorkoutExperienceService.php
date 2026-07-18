<?php

namespace App\Domain\Workout;

use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\DailyWorkoutItem;
use App\Models\GameSession;
use App\Models\ProgressSnapshot;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Build evidence-backed presentation summaries for the continuous workout journey.
 */
final class WorkoutExperienceService
{
    /**
     * Describe one completed game without inventing unavailable performance evidence.
     *
     * @return array{
     *     coaching: string,
     *     detail: string,
     *     performance: string,
     *     next_prompt: string
     * }
     */
    public function transitionSummary(DailyWorkoutItem $completedItem): array
    {
        $completedItem->loadMissing([
            'game',
            'sessions',
            'workout.items.game',
        ]);
        $session = $completedItem->sessions
            ->where('status', SessionStatus::Completed)
            ->sortByDesc('completed_at')
            ->first();
        $nextItem = $completedItem->workout->items->first(
            fn (DailyWorkoutItem $item): bool => $item->position > $completedItem->position,
        );
        $nextSkill = $nextItem?->game->skill_keys[0] ?? null;
        $nextPrompt = $nextItem === null
            ? 'Your full daily rhythm is complete.'
            : 'Next, shift into '.Str::lower(Str::headline((string) $nextSkill)).'.';

        if ($session === null) {
            return [
                'coaching' => 'Step complete.',
                'detail' => 'You stayed with the full practice step.',
                'performance' => 'Completed · no score recorded',
                'next_prompt' => $nextPrompt,
            ];
        }

        return [
            'coaching' => $this->coachingFor($session),
            'detail' => $this->coachingDetailFor($session),
            'performance' => $this->performanceFor($session),
            'next_prompt' => $nextPrompt,
        ];
    }

    /**
     * Build the compact, scalable workout position model used by every journey phase.
     *
     * @return list<array{label: string, position: int, state: string}>
     */
    public function journey(DailyWorkout $workout, ?int $currentItemId = null): array
    {
        $workout->loadMissing('items.game');

        return $workout->items
            ->map(function (DailyWorkoutItem $item) use ($currentItemId): array {
                $state = match (true) {
                    $item->status === WorkoutStatus::Completed => 'completed',
                    $item->getKey() === $currentItemId => 'current',
                    default => 'upcoming',
                };

                return [
                    'label' => $item->game->name,
                    'position' => $item->position,
                    'state' => $state,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Build celebration-first completion evidence for the workout and its Today progress step.
     *
     * @return array{
     *     achievement_description: string|null,
     *     achievement_title: string|null,
     *     best_moment_detail: string,
     *     best_moment_title: string,
     *     coaching: string,
     *     skill_improvements: list<array{change: string, skill: string}>,
     *     streak: int,
     *     streak_message: string
     * }
     */
    public function completionSummary(DailyWorkout $workout): array
    {
        $workout->loadMissing([
            'profile',
            'items.game',
            'items.sessions.game',
        ]);
        $sessions = $workout->items
            ->flatMap->sessions
            ->where('status', SessionStatus::Completed);
        $evidenceSessions = $sessions->reject(
            fn (GameSession $session): bool => $session->isFrameworkPlaceholder(),
        );
        $sessionIds = $sessions->pluck('id')->all();
        $skillImprovements = ProgressSnapshot::query()
            ->whereIn('game_session_id', $sessionIds)
            ->where('delta', '>', 0)
            ->latest('delta')
            ->latest('recorded_at')
            ->get()
            ->unique(fn (ProgressSnapshot $snapshot): string => $snapshot->skill_key->value)
            ->take(3)
            ->map(fn (ProgressSnapshot $snapshot): array => [
                'skill' => Str::headline($snapshot->skill_key->value),
                'change' => '+'.$snapshot->delta,
            ])
            ->values()
            ->all();
        $achievement = AchievementUnlock::query()
            ->whereBelongsTo($workout->profile)
            ->where(function (Builder $query) use ($workout, $sessionIds): void {
                $query
                    ->whereBelongsTo($workout, 'dailyWorkout')
                    ->when(
                        $sessionIds !== [],
                        fn (Builder $query): Builder => $query->orWhereIn('game_session_id', $sessionIds),
                    );
            })
            ->with('achievement')
            ->latest('unlocked_at')
            ->latest('id')
            ->first();
        $streak = (int) (Statistic::query()
            ->whereBelongsTo($workout->profile)
            ->overall()
            ->first()
            ?->current_streak ?? 0);
        $bestSession = $evidenceSessions
            ->sortByDesc(fn (GameSession $session): int => $session->score ?? 0)
            ->first();
        [$bestMomentTitle, $bestMomentDetail] = $this->bestMoment($bestSession);
        $accuracy = data_get($workout->summary, 'accuracy');

        return [
            'achievement_description' => $achievement?->achievement->description,
            'achievement_title' => $achievement?->achievement->name,
            'best_moment_detail' => $bestMomentDetail,
            'best_moment_title' => $bestMomentTitle,
            'coaching' => $this->completionCoaching(is_numeric($accuracy) ? (float) $accuracy : null),
            'skill_improvements' => $skillImprovements,
            'streak' => (int) $streak,
            'streak_message' => $streak === 1
                ? 'Your daily rhythm has started.'
                : ($streak > 1 ? $streak.' focused days in rhythm.' : 'Today is a fresh point in your rhythm.'),
        ];
    }

    private function coachingFor(GameSession $session): string
    {
        return match (true) {
            ($session->accuracy ?? 0) >= 90 => 'Excellent control.',
            ($session->accuracy ?? 0) >= 75 => 'Great focus.',
            ($session->accuracy ?? 0) >= 60 => 'Nice recovery.',
            default => 'You stayed with it.',
        };
    }

    private function coachingDetailFor(GameSession $session): string
    {
        return match (true) {
            $session->best_combo >= 4 => 'You found a strong rhythm and held it.',
            ($session->accuracy ?? 0) >= 80 => 'Your attention stayed steady as the rules shifted.',
            $session->correct_count > $session->missed_count => 'You recovered more signals than you missed.',
            default => 'Each reset protected the next decision.',
        };
    }

    private function performanceFor(GameSession $session): string
    {
        $parts = [];

        if ($session->accuracy !== null) {
            $parts[] = rtrim(rtrim(number_format($session->accuracy, 1), '0'), '.').'% accuracy';
        }

        if ($session->score !== null) {
            $parts[] = number_format($session->score).' points';
        }

        if ($session->best_combo > 1) {
            $parts[] = 'x'.$session->best_combo.' focus chain';
        }

        return $parts === [] ? 'Completed with recorded gameplay evidence' : implode(' · ', $parts);
    }

    /**
     * @return array{string, string}
     */
    private function bestMoment(?GameSession $session): array
    {
        if ($session === null) {
            return [
                'Full sequence complete',
                'You followed the complete daily rhythm from preparation to finish.',
            ];
        }

        $previousBest = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->whereBelongsTo($session->game)
            ->completed()
            ->withGameplayEvidence()
            ->whereKeyNot($session->getKey())
            ->max('score');

        if ($session->score !== null && ($previousBest === null || $session->score > $previousBest)) {
            return [
                $previousBest === null ? 'First benchmark set' : 'New personal best',
                number_format($session->score).' points in '.$session->game->name.'.',
            ];
        }

        if ($session->best_combo > 1) {
            return [
                'Strongest focus chain',
                'You held an x'.$session->best_combo.' chain in '.$session->game->name.'.',
            ];
        }

        if ($session->accuracy !== null) {
            return [
                'Steady attention',
                rtrim(rtrim(number_format($session->accuracy, 1), '0'), '.').'% accuracy in '.$session->game->name.'.',
            ];
        }

        return [
            $session->game->name.' complete',
            'Your recorded session is saved privately on this device.',
        ];
    }

    private function completionCoaching(?float $accuracy): string
    {
        return match (true) {
            $accuracy === null => 'You completed the full rhythm.',
            $accuracy >= 90 => 'Excellent control today.',
            $accuracy >= 75 => 'Great focus today.',
            $accuracy >= 60 => 'Nice recovery today.',
            default => 'You kept returning to the signal.',
        };
    }
}
