<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Progress\ProgressService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Domain\Workout\WorkoutService;
use App\Enums\WorkoutStatus;
use App\Models\Achievement;
use App\Models\DailyWorkout;
use App\Models\Profile as LocalProfile;
use App\Models\ProgressSnapshot;
use App\Models\Statistic;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

#[Lazy]
final class Progress extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your progress could not be loaded. Please try again.';

    public bool $isStatisticsLoading = true;

    public bool $isSkillsLoading = true;

    public bool $isAchievementsLoading = true;

    public ?string $statisticsError = null;

    public ?string $skillsError = null;

    public ?string $achievementsError = null;

    public int $currentStreak = 0;

    public int $longestStreak = 0;

    /**
     * @var list<array{label: string, active: bool, today: bool}>
     */
    public array $weeklyDays = [];

    public int $weeklyCompleted = 0;

    /**
     * @var list<array{label: string, score: int, progress: float, delta: int, deltaLabel: string}>
     */
    public array $skills = [];

    public bool $hasTrainingEvidence = false;

    public string $workoutsLabel = '0';

    public string $sessionsLabel = '0';

    public string $trainingTimeLabel = 'None yet';

    public string $accuracyLabel = 'Not measured';

    public string $responseLabel = 'Not measured';

    public string $comboLabel = 'None yet';

    /**
     * @var list<array{name: string, best: string, sessions: string, accuracy: string}>
     */
    public array $gameBests = [];

    /**
     * @var list<array{name: string, description: string, unlocked: bool, detail: string}>
     */
    public array $achievements = [];

    public int $achievementsUnlocked = 0;

    public int $achievementsTotal = 0;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    /**
     * Apply the saved theme, enforce onboarding, and assemble local evidence.
     */
    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $transition = $theme->prefersReducedMotion()
                ? Transition::None
                : Transition::Fade;

            $this->replace('/onboarding')->transition($transition);

            return;
        }

        $this->loadScreen();
    }

    public function render(): Element
    {
        return $this->view('screens.progress');
    }

    /**
     * Refresh local evidence after returning from another native screen.
     */
    public function onResume(): void
    {
        $this->loadScreen();
    }

    /**
     * Supply the Progress title and purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Progress')
            ->subtitle('Evidence from your local training')
            ->back(false);
    }

    /**
     * Retry only the rhythm, training, and personal-best section.
     */
    public function retryStatistics(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadStatistics($profile);
        }
    }

    /**
     * Retry only the skill profile section.
     */
    public function retrySkills(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadSkills($profile);
        }
    }

    /**
     * Retry only the achievements section.
     */
    public function retryAchievements(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadAchievements($profile);
        }
    }

    /**
     * Retry the complete screen after a profile-level failure.
     */
    public function retryProgressScreen(): void
    {
        $this->loadScreen();
    }

    private function loadScreen(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);

            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);

            $this->loadStatistics($profile);
            $this->loadSkills($profile);
            $this->loadAchievements($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function loadStatistics(LocalProfile $profile): void
    {
        $this->isStatisticsLoading = true;
        $this->statisticsError = null;

        try {
            $statistics = app(StatisticsService::class);
            $overview = $statistics->overview($profile);

            $this->currentStreak = $overview?->current_streak ?? 0;
            $this->longestStreak = $overview?->longest_streak ?? 0;
            $this->hasTrainingEvidence = $overview !== null
                && ($overview->workouts_completed > 0 || $overview->sessions_completed > 0);
            $this->workoutsLabel = (string) ($overview?->workouts_completed ?? 0);
            $this->sessionsLabel = (string) ($overview?->sessions_completed ?? 0);
            $this->trainingTimeLabel = $this->formatTrainingTime($overview?->training_seconds ?? 0);
            $this->accuracyLabel = $overview?->accuracy === null
                ? 'Not measured'
                : round($overview->accuracy).'%';
            $this->responseLabel = $this->formatResponseTime($overview?->average_response_ms);
            $this->comboLabel = ($overview?->longest_combo ?? 0) > 0
                ? 'x'.$overview->longest_combo
                : 'None yet';

            $this->mapWeeklyDays(app(WorkoutService::class)->history($profile));
            $this->mapGameBests($statistics->personalBests($profile));
        } catch (Throwable $exception) {
            report($exception);

            $this->statisticsError = 'Your training summary is temporarily unavailable.';
        } finally {
            $this->isStatisticsLoading = false;
        }
    }

    private function loadSkills(LocalProfile $profile): void
    {
        $this->isSkillsLoading = true;
        $this->skillsError = null;

        try {
            $this->skills = app(ProgressService::class)->latestSnapshots($profile)
                ->map(fn (ProgressSnapshot $snapshot): array => [
                    'label' => Str::headline($snapshot->skill_key->value),
                    'score' => $snapshot->score_after,
                    'progress' => round($snapshot->score_after / 1000, 3),
                    'delta' => $snapshot->delta,
                    'deltaLabel' => $snapshot->delta > 0
                        ? '+'.$snapshot->delta
                        : ($snapshot->delta < 0 ? (string) $snapshot->delta : '±0'),
                ])
                ->values()
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            $this->skillsError = 'Skill progress could not be summarized right now.';
        } finally {
            $this->isSkillsLoading = false;
        }
    }

    private function loadAchievements(LocalProfile $profile): void
    {
        $this->isAchievementsLoading = true;
        $this->achievementsError = null;

        try {
            $overview = app(AchievementService::class)->overview($profile);

            $this->achievementsTotal = $overview->count();
            $this->achievementsUnlocked = $overview
                ->filter(fn (Achievement $achievement): bool => $achievement->unlocks->isNotEmpty())
                ->count();
            $this->achievements = $overview
                ->map(function (Achievement $achievement): array {
                    $unlock = $achievement->unlocks->first();

                    return [
                        'name' => $achievement->name,
                        'description' => $achievement->description,
                        'unlocked' => $unlock !== null,
                        'detail' => $unlock !== null
                            ? 'Unlocked '.$unlock->unlocked_at->format('M j, Y')
                            : ($achievement->game !== null
                                ? 'Locked · '.$achievement->game->name
                                : 'Locked'),
                    ];
                })
                ->values()
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            $this->achievementsError = 'Your achievements could not be displayed right now.';
        } finally {
            $this->isAchievementsLoading = false;
        }
    }

    /**
     * @param  Collection<int, DailyWorkout>  $history
     */
    private function mapWeeklyDays(Collection $history): void
    {
        $completedDates = $history
            ->where('status', WorkoutStatus::Completed)
            ->map(fn (DailyWorkout $workout): string => $workout->workout_date->format('Y-m-d'))
            ->all();
        $days = [];
        $completedCount = 0;

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = today()->subDays($offset);
            $active = in_array($date->format('Y-m-d'), $completedDates, true);

            if ($active) {
                $completedCount++;
            }

            $days[] = [
                'label' => substr($date->format('D'), 0, 1),
                'active' => $active,
                'today' => $offset === 0,
            ];
        }

        $this->weeklyDays = $days;
        $this->weeklyCompleted = $completedCount;
    }

    /**
     * @param  Collection<int, Statistic>  $personalBests
     */
    private function mapGameBests(Collection $personalBests): void
    {
        $this->gameBests = $personalBests
            ->filter(fn (Statistic $statistic): bool => $statistic->game !== null
                && $statistic->sessions_completed > 0)
            ->map(fn (Statistic $statistic): array => [
                'name' => $statistic->game->name,
                'best' => (string) ($statistic->best_score ?? 0),
                'sessions' => $statistic->sessions_completed === 1
                    ? '1 session'
                    : $statistic->sessions_completed.' sessions',
                'accuracy' => $statistic->accuracy === null
                    ? 'Accuracy not measured'
                    : round($statistic->accuracy).'% accuracy',
            ])
            ->values()
            ->all();
    }

    private function formatTrainingTime(int $trainingSeconds): string
    {
        if ($trainingSeconds < 1) {
            return 'None yet';
        }

        if ($trainingSeconds < 60) {
            return 'Under a minute';
        }

        $totalMinutes = intdiv($trainingSeconds, 60);

        if ($totalMinutes < 60) {
            return $totalMinutes.' min';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return $minutes === 0
            ? $hours.' h'
            : $hours.' h '.$minutes.' min';
    }

    private function formatResponseTime(?int $averageResponseMs): string
    {
        if ($averageResponseMs === null) {
            return 'Not measured';
        }

        if ($averageResponseMs < 1000) {
            return $averageResponseMs.' ms';
        }

        return number_format($averageResponseMs / 1000, 1).' s';
    }
}
