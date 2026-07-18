<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Workout\WorkoutExperienceService;
use App\Domain\Workout\WorkoutService;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkoutItem;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class WorkoutTransition extends NativeComponent
{
    public string $screenState = 'content';

    public string $errorMessage = 'The next workout step could not be loaded. Return to the workout to continue.';

    public ?int $completedItemId = null;

    public string $previousGame = '';

    public string $nextGame = '';

    public string $performanceMessage = 'This step did not record gameplay evidence.';

    public string $coaching = 'Great focus.';

    public string $coachingDetail = '';

    public string $nextPrompt = '';

    public int $gamesRemaining = 0;

    public float $progress = 0.0;

    public string $timeEstimate = '';

    public int $autoTransitionSeconds = 4;

    public bool $autoTransitionEnabled = true;

    public bool $isTransitioning = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public bool $isFinalGame = false;

    public ?int $completedWorkoutId = null;

    /**
     * @var list<array{label: string, position: int, state: string}>
     */
    public array $journeySteps = [];

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->completedItemId = (int) $this->param('item');
        $this->loadTransition();
    }

    public function render(): Element
    {
        return $this->view('screens.workout-transition');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    #[Poll(1000)]
    public function advanceAutoTransition(): void
    {
        if (! $this->autoTransitionEnabled || $this->isTransitioning || $this->screenState !== 'content') {
            return;
        }

        if ($this->autoTransitionSeconds > 1) {
            $this->autoTransitionSeconds--;

            return;
        }

        $this->continueWorkout();
    }

    public function continueWorkout(): void
    {
        if ($this->isTransitioning) {
            return;
        }

        $profile = app(ProfileService::class)->current();
        $completedItem = $this->completedItem();

        if ($profile === null || $completedItem === null) {
            $this->screenState = 'error';

            return;
        }

        if ($this->isFinalGame) {
            if ($this->completedWorkoutId === null) {
                $this->screenState = 'error';

                return;
            }

            $this->isTransitioning = true;
            $this->replace(
                $this->route('native.workout.complete', ['workout' => $this->completedWorkoutId]),
            )->transition($this->screenTransition());

            return;
        }

        $nextItem = $completedItem?->workout->items->first(
            fn ($item): bool => $item->position > $completedItem->position
                && $item->status !== WorkoutStatus::Completed,
        );

        if ($nextItem === null) {
            $this->screenState = 'error';

            return;
        }

        $this->isTransitioning = true;
        $session = app(GameSessionService::class)->startForWorkoutItem($profile, $nextItem);
        $this->replace(
            $this->route('native.workout.preparation', ['session' => $session->getKey()]),
        )->transition($this->screenTransition());
    }

    public function returnToWorkout(): void
    {
        $this->replace('/workout')->transition($this->screenTransition());
    }

    public function onBackPressed(): void
    {
        $this->returnToWorkout();
    }

    private function loadTransition(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $completedItem = $this->completedItem();

            if ($profile === null
                || $completedItem === null
                || $completedItem->status !== WorkoutStatus::Completed) {
                $this->screenState = 'error';

                return;
            }

            $workout = $completedItem->workout;
            $nextItem = $workout->items->first(
                fn ($item): bool => $item->position > $completedItem->position
                    && $item->status !== WorkoutStatus::Completed,
            );

            $settings = app(SettingsService::class)->forProfile($profile);
            $experience = app(WorkoutExperienceService::class);
            $summary = $experience->transitionSummary($completedItem);
            $this->previousGame = $completedItem->game->name;
            $this->isFinalGame = $nextItem === null;
            $this->nextGame = $nextItem?->game->name ?? 'Workout celebration';
            $this->coaching = $summary['coaching'];
            $this->coachingDetail = $summary['detail'];
            $this->performanceMessage = $summary['performance'];
            $this->nextPrompt = $summary['next_prompt'];
            $this->gamesRemaining = $workout->items
                ->where('status', '!=', WorkoutStatus::Completed)
                ->count();
            $this->progress = $workout->items->isEmpty()
                ? 0.0
                : round($workout->items->where('status', WorkoutStatus::Completed)->count() / $workout->items->count(), 3);
            $this->timeEstimate = $nextItem === null
                ? 'Daily sequence complete'
                : 'About '.app(WorkoutService::class)->estimatedGameDurationMinutes($nextItem->level).' min remaining';
            $this->journeySteps = $experience->journey($workout, $nextItem?->getKey());

            if ($this->isFinalGame) {
                $wasCompleted = $workout->status === WorkoutStatus::Completed;
                $completedWorkout = app(WorkoutService::class)->complete($workout);
                $this->completedWorkoutId = $completedWorkout->getKey();

                if (! $wasCompleted) {
                    app(HapticService::class)->trigger(HapticFeedback::Success);
                }
            }

            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->autoTransitionEnabled = ! $this->reducedMotion;
            $this->autoTransitionSeconds = $this->autoTransitionEnabled ? 4 : 0;
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function completedItem(): ?DailyWorkoutItem
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null || $this->completedItemId === null) {
            return null;
        }

        return DailyWorkoutItem::query()
            ->whereKey($this->completedItemId)
            ->whereHas(
                'workout',
                fn ($query) => $query->where('profile_id', $profile->getKey()),
            )
            ->with([
                'game',
                'level',
                'sessions',
                'workout.items.game',
                'workout.items.level',
                'workout.items.sessions',
            ])
            ->first();
    }

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::FadeFromBottom;
    }
}
