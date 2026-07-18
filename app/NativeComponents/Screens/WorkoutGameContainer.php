<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Workout\WorkoutService;
use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\GameSession;
use App\NativeUI\Dialogs\InteractsWithDialogs;
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

final class WorkoutGameContainer extends NativeComponent
{
    use InteractsWithDialogs;

    public string $screenState = 'content';

    public string $errorMessage = 'This game checkpoint could not be loaded. Your saved workout remains available.';

    public ?int $sessionId = null;

    public ?int $workoutId = null;

    public string $gameTitle = '';

    public string $gameOrder = '';

    public string $placeholderMessage = '';

    public int $gamesRemaining = 0;

    public float $progress = 0.0;

    public string $timeEstimate = '';

    public int $elapsedSeconds = 0;

    public string $elapsedTime = '0:00';

    public bool $paused = false;

    public bool $isSubmitting = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->sessionId = (int) $this->param('session');
        $this->loadSession();
    }

    public function render(): Element
    {
        return $this->view('screens.workout-game-container');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title($this->gameTitle !== '' ? $this->gameTitle : 'Workout')
            ->subtitle('Game container')
            ->back(false);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    #[Poll(1000)]
    public function tickTimer(): void
    {
        if ($this->screenState !== 'content' || $this->paused || $this->isSubmitting) {
            return;
        }

        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            return;
        }

        $this->elapsedSeconds++;
        $this->elapsedTime = $this->formatDuration($this->elapsedSeconds);
        app(GameSessionService::class)->checkpoint($session, [
            ...($session->state_snapshot ?? []),
            'prepared' => true,
            'paused' => false,
            'elapsed_seconds' => $this->elapsedSeconds,
        ]);
    }

    public function pauseWorkout(): void
    {
        if ($this->isSubmitting) {
            return;
        }

        $this->paused = true;
        $this->bottomSheetVisible = true;
        $this->storeCheckpoint();

        app(HapticService::class)->trigger(HapticFeedback::Impact);
    }

    public function resumeWorkout(): void
    {
        $this->paused = false;
        $this->bottomSheetVisible = false;
        $this->storeCheckpoint();
    }

    public function requestExit(): void
    {
        $this->paused = true;
        $this->bottomSheetVisible = false;
        $this->dialogVisible = true;
        $this->storeCheckpoint();

        app(HapticService::class)->trigger(HapticFeedback::Warning);
    }

    public function cancelExit(): void
    {
        $this->dialogVisible = false;
        $this->paused = false;
        $this->storeCheckpoint();
    }

    public function confirmExit(): void
    {
        $this->dialogVisible = false;
        $this->paused = true;
        $this->storeCheckpoint();

        $this->replace('/')->transition($this->screenTransition());
    }

    public function restartWorkout(): void
    {
        $session = $this->session();

        if ($session === null) {
            $this->screenState = 'error';

            return;
        }

        app(GameSessionService::class)->restartPlaceholder($session);
        $this->bottomSheetVisible = false;
        app(HapticService::class)->trigger(HapticFeedback::Warning);

        $this->replace(
            $this->route('native.workout.preparation', ['session' => $session->getKey()]),
        )->transition($this->screenTransition());
    }

    public function completePlaceholder(): void
    {
        if ($this->isSubmitting) {
            return;
        }

        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        $this->isSubmitting = true;

        try {
            $completedSession = app(GameSessionService::class)->completePlaceholder(
                $session,
                $this->elapsedSeconds,
            );
            $workoutItem = $completedSession->workoutItem;
            $workout = $workoutItem->workout->load(['items.game', 'items.level', 'items.sessions']);
            $nextItem = $workout->items->first(
                fn ($item): bool => $item->position > $workoutItem->position
                    && $item->status !== WorkoutStatus::Completed,
            );

            if ($nextItem !== null) {
                app(HapticService::class)->trigger(HapticFeedback::Selection);
                $this->replace(
                    $this->route('native.workout.transition', ['item' => $workoutItem->getKey()]),
                )->transition($this->screenTransition());

                return;
            }

            $completedWorkout = app(WorkoutService::class)->complete($workout);
            app(HapticService::class)->trigger(HapticFeedback::Success);
            $this->replace(
                $this->route('native.workout.complete', ['workout' => $completedWorkout->getKey()]),
            )->transition($this->screenTransition());
        } catch (Throwable $exception) {
            report($exception);

            $this->isSubmitting = false;
            $this->screenState = 'error';
        }
    }

    public function onBackPressed(): void
    {
        $this->requestExit();
    }

    private function loadSession(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $session = $this->session();

            if ($profile === null
                || $session === null
                || ! $session->isFrameworkPlaceholder()
                || $session->game->type !== GameType::ClearThought) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace(
                    $this->route('native.workout.transition', [
                        'item' => $session->workoutItem->getKey(),
                    ]),
                )->transition(Transition::None);

                return;
            }

            if (! (bool) data_get($session->state_snapshot, 'prepared', false)) {
                $this->replace(
                    $this->route('native.workout.preparation', ['session' => $session->getKey()]),
                )->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $workout = $session->workoutItem->workout;
            $items = $workout->items;
            $completedItems = $items->where('status', WorkoutStatus::Completed)->count();

            $this->workoutId = $workout->getKey();
            $this->gameTitle = $session->game->name;
            $this->gameOrder = 'Game '.$session->workoutItem->position.' of '.$items->count();
            $this->placeholderMessage = $this->placeholderFor();
            $this->gamesRemaining = max(0, $items->count() - $completedItems - 1);
            $this->progress = $items->isEmpty() ? 0.0 : round($completedItems / $items->count(), 3);
            $this->timeEstimate = 'About '.app(WorkoutService::class)->estimatedGameDurationMinutes($session->level).' min';
            $this->elapsedSeconds = max(0, (int) data_get($session->state_snapshot, 'elapsed_seconds', 0));
            $this->elapsedTime = $this->formatDuration($this->elapsedSeconds);
            $this->paused = (bool) data_get($session->state_snapshot, 'paused', false);
            $this->bottomSheetVisible = $this->paused;
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function storeCheckpoint(): void
    {
        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            return;
        }

        app(GameSessionService::class)->checkpoint($session, [
            ...($session->state_snapshot ?? []),
            'prepared' => true,
            'paused' => $this->paused,
            'elapsed_seconds' => $this->elapsedSeconds,
        ]);
    }

    private function session(): ?GameSession
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null || $this->sessionId === null) {
            return null;
        }

        return GameSession::query()
            ->whereKey($this->sessionId)
            ->where('profile_id', $profile->getKey())
            ->with([
                'game',
                'level',
                'workoutItem.workout.items.game',
                'workoutItem.workout.items.level',
                'workoutItem.workout.items.sessions',
            ])
            ->first();
    }

    private function placeholderFor(): string
    {
        return 'Clear Thought gameplay is intentionally not implemented. Complete this placeholder without recording answers, score, or skill evidence.';
    }

    private function formatDuration(int $seconds): string
    {
        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::Fade;
    }
}
