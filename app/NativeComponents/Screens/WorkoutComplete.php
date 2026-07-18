<?php

namespace App\NativeComponents\Screens;

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Workout\WorkoutExperienceService;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Str;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class WorkoutComplete extends NativeComponent
{
    public string $screenState = 'content';

    public string $errorMessage = 'This workout summary is not available yet.';

    public ?int $workoutId = null;

    public string $duration = 'Under 1 min';

    public int $gamesCompleted = 0;

    /**
     * @var list<string>
     */
    public array $skills = [];

    public string $scoreSummary = 'Not recorded';

    public string $accuracySummary = 'Not recorded';

    public string $progressMessage = 'Complete Signal Shift evidence is reflected in your local skill progress.';

    public string $phase = 'celebration';

    public string $coaching = 'You completed the full rhythm.';

    /**
     * @var list<array{change: string, skill: string}>
     */
    public array $skillImprovements = [];

    public string $bestMomentTitle = 'Full sequence complete';

    public string $bestMomentDetail = '';

    public int $streak = 0;

    public string $streakMessage = '';

    public ?string $achievementTitle = null;

    public ?string $achievementDescription = null;

    /**
     * @var list<array{label: string, position: int, state: string}>
     */
    public array $journeySteps = [];

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->workoutId = (int) $this->param('workout');
        $this->loadWorkout();
    }

    public function render(): Element
    {
        return $this->view('screens.workout-complete');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function continueHome(): void
    {
        $this->replace('/', [
            'workout_completed' => true,
            'workout_id' => $this->workoutId,
        ])->transition(
            $this->reducedMotion ? Transition::None : Transition::Fade,
        );
    }

    public function showTodayProgress(): void
    {
        $this->phase = 'progress';
        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    public function onBackPressed(): void
    {
        $this->continueHome();
    }

    private function loadWorkout(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $workout = $this->workout($profile?->getKey());

            if ($profile === null || $workout === null) {
                $this->screenState = 'error';

                return;
            }

            if ($workout->status !== WorkoutStatus::Completed) {
                $this->replace('/workout')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $summary = $workout->summary ?? [];

            $this->duration = $this->formatDuration($workout->training_seconds);
            $this->gamesCompleted = $workout->items
                ->where('status', WorkoutStatus::Completed)
                ->count();
            $this->skills = $workout->items
                ->flatMap(fn ($item): array => $item->game->skill_keys)
                ->unique()
                ->map(fn (string $skill): string => Str::headline($skill))
                ->values()
                ->all();
            $this->scoreSummary = data_get($summary, 'score') === null
                ? 'Not recorded'
                : (string) data_get($summary, 'score');
            $this->accuracySummary = data_get($summary, 'accuracy') === null
                ? 'Not recorded'
                : data_get($summary, 'accuracy').'%';
            $this->progressMessage = (bool) data_get($summary, 'has_gameplay_evidence', false)
                ? 'Signal Shift evidence updated your local skill progress, statistics, and eligible achievements.'
                : 'Skill progress was not recorded because no gameplay evidence was available.';
            $experience = app(WorkoutExperienceService::class);
            $completion = $experience->completionSummary($workout);
            $this->coaching = $completion['coaching'];
            $this->skillImprovements = $completion['skill_improvements'];
            $this->bestMomentTitle = $completion['best_moment_title'];
            $this->bestMomentDetail = $completion['best_moment_detail'];
            $this->streak = $completion['streak'];
            $this->streakMessage = $completion['streak_message'];
            $this->achievementTitle = $completion['achievement_title'];
            $this->achievementDescription = $completion['achievement_description'];
            $this->journeySteps = $experience->journey($workout);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Success);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function workout(?int $profileId): ?DailyWorkout
    {
        if ($profileId === null || $this->workoutId === null) {
            return null;
        }

        return DailyWorkout::query()
            ->whereKey($this->workoutId)
            ->where('profile_id', $profileId)
            ->with(['items.game', 'items.level', 'items.sessions'])
            ->first();
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds === 0 ? 'Under 1 min' : $seconds.' sec';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return $remainingSeconds === 0
            ? $minutes.' min'
            : $minutes.' min '.$remainingSeconds.' sec';
    }
}
