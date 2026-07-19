<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Progress\ProgressService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Domain\Workout\WorkoutExperienceService;
use App\Domain\Workout\WorkoutService;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\Profile;
use App\Models\Statistic;
use App\NativeUI\Dialogs\InteractsWithDialogs;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use App\NativeUI\Home\GreetingResolver;
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
final class Home extends NativeComponent
{
    use InteractsWithDialogs;

    public string $dashboardState = 'content';

    public string $dashboardError = 'Your dashboard could not be loaded. Please try again.';

    public bool $isWorkoutLoading = true;

    public bool $isStatisticsLoading = true;

    public bool $isProgressLoading = true;

    public bool $isAchievementLoading = true;

    public ?string $workoutError = null;

    public ?string $statisticsError = null;

    public ?string $progressError = null;

    public ?string $achievementError = null;

    public string $greeting = '';

    public string $displayName = 'friend';

    public string $todayLabel = '';

    public string $avatarInitial = '';

    public string $greetingMessage = 'A small focused step is ready when you are.';

    public bool $returningUser = false;

    public bool $hasWorkoutHistory = false;

    public string $workoutTitle = 'Daily Momentum';

    public string $workoutDuration = 'About 5 min';

    /**
     * @var list<string>
     */
    public array $workoutSkills = [];

    public string $workoutDifficulty = '';

    public string $workoutAction = 'Start Training';

    public string $workoutStatus = WorkoutStatus::Pending->value;

    public int $workoutGameCount = 0;

    public int $workoutCompletionPercentage = 0;

    public int $currentStreak = 0;

    public int $longestStreak = 0;

    /**
     * @var list<array{label: string, score: int, progress: float}>
     */
    public array $skillHighlights = [];

    public int $weeklyCompleted = 0;

    public int $weeklyCompletionPercentage = 0;

    public ?int $personalBestScore = null;

    public ?string $personalBestGame = null;

    public ?string $achievementTitle = null;

    public ?string $achievementDescription = null;

    public bool $celebrateWorkoutReturn = false;

    public string $workoutReturnMessage = '';

    public string $workoutReturnStreak = '';

    public ?string $workoutReturnAchievement = null;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public float $pressScale = 1.0;

    public float $pressOpacity = 1.0;

    /**
     * Apply the saved theme, enforce onboarding, and assemble local dashboard previews.
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

        $this->celebrateWorkoutReturn = (bool) $this->data('workout_completed', false);
        $this->loadDashboard();

        if ($this->celebrateWorkoutReturn) {
            $this->loadWorkoutReturn();
        }
    }

    public function render(): Element
    {
        return $this->view('screens.home');
    }

    /**
     * Refresh local previews after returning from another native screen.
     */
    public function onResume(): void
    {
        $this->loadDashboard();
    }

    /**
     * Supply the Home title and a concise dashboard purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Home')
            ->subtitle('Your daily training overview')
            ->back(false);
    }

    /**
     * Navigate to today's workout flow.
     */
    public function openWorkout(): void
    {
        if ($this->workoutStatus === WorkoutStatus::Completed->value || $this->workoutError !== null) {
            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Impact);

        $navigation = $this->navigate('/workout');

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Retry only the workout section after a recoverable local failure.
     */
    public function retryWorkout(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null) {
            return;
        }

        $this->loadWorkout($profile);

        if ($this->workoutError !== null) {
            app(ToastService::class)->show(
                'Today’s workout is still unavailable.',
                ToastType::Error,
            );
        }
    }

    /**
     * Retry only the statistics section.
     */
    public function retryStatistics(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadStatistics($profile);
        }
    }

    /**
     * Retry only the progress section.
     */
    public function retryProgress(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadProgress($profile);
        }
    }

    /**
     * Retry only the achievement section.
     */
    public function retryAchievement(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->loadAchievement($profile);
        }
    }

    /**
     * Retry the complete dashboard after a profile-level failure.
     */
    public function retryDashboard(): void
    {
        $this->loadDashboard();
    }

    private function loadDashboard(): void
    {
        $this->dashboardState = 'content';
        $this->dashboardError = 'Your dashboard could not be loaded. Please try again.';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $greetings = app(GreetingResolver::class);

            $this->greeting = $greetings->greeting(now());
            $this->displayName = $greetings->displayName($profile->display_name);
            $this->todayLabel = now()->format('l, M j');
            $this->avatarInitial = Str::upper(Str::substr($this->displayName, 0, 1));
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->pressScale = $this->reducedMotion ? 1.0 : 0.985;
            $this->pressOpacity = $this->reducedMotion
                ? 1.0
                : DesignTokens::OPACITY['pressed'];

            $this->loadWorkout($profile);
            $this->loadStatistics($profile);
            $this->loadProgress($profile);
            $this->loadAchievement($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->dashboardState = 'error';
        }
    }

    private function loadWorkout(Profile $profile): void
    {
        $this->isWorkoutLoading = true;
        $this->workoutError = null;

        try {
            $workouts = app(WorkoutService::class);
            $workout = $workouts->generateToday($profile);
            $history = $workouts->history($profile);

            $this->workoutDuration = 'About '.$workouts->estimatedDurationMinutes($workout).' min';
            $this->mapWorkout($workout, $profile);
            $this->mapHistory($history);
        } catch (Throwable $exception) {
            report($exception);

            $this->workoutError = 'Today’s workout could not be prepared. Your local progress is safe.';
        } finally {
            $this->isWorkoutLoading = false;
        }
    }

    private function loadStatistics(Profile $profile): void
    {
        $this->isStatisticsLoading = true;
        $this->statisticsError = null;

        try {
            $statistics = app(StatisticsService::class);
            $overview = $statistics->overview($profile);
            $personalBest = $statistics->personalBests($profile)
                ->sortByDesc(fn (Statistic $statistic): int => $statistic->best_score ?? 0)
                ->first();

            $this->currentStreak = $overview?->current_streak ?? 0;
            $this->longestStreak = $overview?->longest_streak ?? 0;
            $this->personalBestScore = $personalBest?->best_score;
            $this->personalBestGame = $personalBest?->game?->name;
        } catch (Throwable $exception) {
            report($exception);

            $this->statisticsError = 'Streak and personal-best previews are temporarily unavailable.';
        } finally {
            $this->isStatisticsLoading = false;
        }
    }

    private function loadProgress(Profile $profile): void
    {
        $this->isProgressLoading = true;
        $this->progressError = null;

        try {
            $this->skillHighlights = collect(app(ProgressService::class)->currentSkillValues($profile))
                ->sortDesc()
                ->take(3)
                ->map(fn (int $score, string $skill): array => [
                    'label' => Str::headline($skill),
                    'score' => $score,
                    'progress' => round($score / 1000, 3),
                ])
                ->values()
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            $this->progressError = 'Skill progress could not be summarized right now.';
        } finally {
            $this->isProgressLoading = false;
        }
    }

    private function loadAchievement(Profile $profile): void
    {
        $this->isAchievementLoading = true;
        $this->achievementError = null;

        try {
            $unlock = app(AchievementService::class)->latestUnlock($profile);

            $this->achievementTitle = $unlock?->achievement->name;
            $this->achievementDescription = $unlock?->achievement->description;
        } catch (Throwable $exception) {
            report($exception);

            $this->achievementError = 'Your latest achievement could not be displayed right now.';
        } finally {
            $this->isAchievementLoading = false;
        }
    }

    private function mapWorkout(DailyWorkout $workout, Profile $profile): void
    {
        $items = $workout->items;
        $completedItems = $items->where('status', WorkoutStatus::Completed)->count();
        $itemCount = $items->count();

        $this->workoutStatus = $workout->status->value;
        $this->workoutGameCount = $itemCount;
        $this->workoutAction = match ($workout->status) {
            WorkoutStatus::Pending => 'Start Training',
            WorkoutStatus::InProgress => 'Continue Training',
            WorkoutStatus::Completed => 'Completed Today',
        };
        $this->workoutCompletionPercentage = $workout->status === WorkoutStatus::Completed
            ? 100
            : ($itemCount === 0 ? 0 : (int) round(($completedItems / $itemCount) * 100));
        $this->workoutDifficulty = $profile->difficulty_preference->label();
        $this->workoutSkills = $items
            ->flatMap(fn ($item): array => $item->game->skill_keys)
            ->unique()
            ->take(4)
            ->map(fn (string $skill): string => Str::headline($skill))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DailyWorkout>  $history
     */
    private function mapHistory(Collection $history): void
    {
        $completedHistory = $history->where('status', WorkoutStatus::Completed);
        $weekStart = today()->subDays(6)->startOfDay();

        $this->hasWorkoutHistory = $completedHistory->isNotEmpty();
        $this->returningUser = $history->contains(
            fn (DailyWorkout $workout): bool => $workout->workout_date->isBefore(today())
                || $workout->status === WorkoutStatus::Completed,
        );
        $this->weeklyCompleted = $completedHistory
            ->filter(fn (DailyWorkout $workout): bool => $workout->workout_date->greaterThanOrEqualTo($weekStart))
            ->count();
        $this->weeklyCompletionPercentage = (int) round(($this->weeklyCompleted / 7) * 100);
        $this->greetingMessage = $this->returningUser
            ? 'Welcome back. Your next focused step is ready.'
            : 'A small focused step is ready when you are.';
    }

    private function loadWorkoutReturn(): void
    {
        $profile = app(ProfileService::class)->current();
        $workoutId = (int) $this->data('workout_id', 0);

        if ($profile === null || $workoutId < 1) {
            $this->celebrateWorkoutReturn = false;

            return;
        }

        $workout = DailyWorkout::query()
            ->whereKey($workoutId)
            ->whereBelongsTo($profile)
            ->completed()
            ->first();

        if ($workout === null) {
            $this->celebrateWorkoutReturn = false;

            return;
        }

        $summary = app(WorkoutExperienceService::class)->completionSummary($workout);
        $this->workoutReturnMessage = $summary['coaching'];
        $this->workoutReturnStreak = $summary['streak_message'];
        $this->workoutReturnAchievement = $summary['achievement_title'];
    }
}
