<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Workout\WorkoutService;
use App\Enums\GameType;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\GameSession;
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

final class WorkoutIntroduction extends NativeComponent
{
    public string $screenState = 'content';

    public string $errorMessage = 'Today’s workout could not be prepared. Your local progress is safe.';

    public ?int $workoutId = null;

    public string $workoutTitle = 'Daily Momentum';

    public string $duration = '';

    public string $difficulty = '';

    public string $actionLabel = 'Begin Workout';

    public string $motivation = 'Settle in, focus on one moment at a time, and let the sequence carry you forward.';

    /**
     * @var list<array{name: string, duration: string}>
     */
    public array $games = [];

    /**
     * @var list<string>
     */
    public array $skills = [];

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding')->transition(
                $theme->prefersReducedMotion() ? Transition::None : Transition::Fade,
            );

            return;
        }

        $this->loadWorkout();
    }

    public function render(): Element
    {
        return $this->view('screens.workout-introduction');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Today’s Workout')
            ->subtitle('Focused daily sequence')
            ->back(false);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function beginWorkout(): void
    {
        $profile = app(ProfileService::class)->current();
        $workout = $this->workout($profile?->getKey());

        if ($profile === null || $workout === null) {
            $this->screenState = 'error';

            return;
        }

        if ($workout->status === WorkoutStatus::Completed) {
            $this->replace(
                $this->route('native.workout.complete', ['workout' => $workout->getKey()]),
            )->transition($this->screenTransition());

            return;
        }

        $workoutItem = $workout->items->first(
            fn ($item): bool => $item->status !== WorkoutStatus::Completed,
        );

        if ($workoutItem === null) {
            $completedWorkout = app(WorkoutService::class)->complete($workout);
            $this->replace(
                $this->route('native.workout.complete', ['workout' => $completedWorkout->getKey()]),
            )->transition($this->screenTransition());

            return;
        }

        $session = app(GameSessionService::class)->startForWorkoutItem($profile, $workoutItem);
        $destination = data_get($session->state_snapshot, 'prepared', false)
            ? $this->gameDestination($session)
            : 'native.workout.preparation';

        app(HapticService::class)->trigger(HapticFeedback::Impact);

        $this->replace(
            $this->route($destination, ['session' => $session->getKey()]),
        )->transition($this->screenTransition());
    }

    public function goBack(): void
    {
        $this->back();
    }

    public function retry(): void
    {
        $this->loadWorkout();
    }

    private function loadWorkout(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $workouts = app(WorkoutService::class);
            $workout = $workouts->generateToday($profile);

            $this->workoutId = $workout->getKey();
            $this->duration = 'About '.$workouts->estimatedDurationMinutes($workout).' min';
            $this->difficulty = $profile->difficulty_preference->label();
            $this->actionLabel = $workout->status === WorkoutStatus::Pending
                ? 'Begin Workout'
                : 'Resume Workout';
            $this->games = $workout->items
                ->map(fn ($item): array => [
                    'name' => $item->game->name,
                    'duration' => 'About '.$workouts->estimatedGameDurationMinutes($item->level).' min',
                ])
                ->values()
                ->all();
            $this->skills = $workout->items
                ->flatMap(fn ($item): array => $item->game->skill_keys)
                ->unique()
                ->map(fn (string $skill): string => Str::headline($skill))
                ->values()
                ->all();
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
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

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::Fade;
    }

    private function gameDestination(GameSession $session): string
    {
        return $session->game->type === GameType::SignalShift
            ? 'native.workout.signal-shift'
            : 'native.workout.game';
    }
}
