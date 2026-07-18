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

final class WorkoutPreparation extends NativeComponent
{
    public string $screenState = 'content';

    public string $errorMessage = 'This workout checkpoint is unavailable. Return to the workout and try again.';

    public ?int $sessionId = null;

    public string $gameTitle = '';

    public string $gameOrder = '';

    public string $instructions = '';

    public int $gamesRemaining = 0;

    public float $progress = 0.0;

    public string $timeEstimate = '';

    public int $countdown = 3;

    public string $countdownAnnouncement = 'Get ready. 3.';

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
        return $this->view('screens.workout-preparation');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Prepare')
            ->subtitle($this->gameTitle !== '' ? $this->gameTitle : 'Workout')
            ->back(false);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    #[Poll(1000)]
    public function advanceCountdown(): void
    {
        if ($this->screenState !== 'content' || $this->countdown <= 0) {
            return;
        }

        if ($this->countdown > 1) {
            $this->countdown--;
            $this->countdownAnnouncement = 'Get ready. '.$this->countdown.'.';

            return;
        }

        $this->startGame();
    }

    public function startGame(): void
    {
        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        $this->countdown = 0;
        $this->countdownAnnouncement = 'Begin '.$this->gameTitle.'.';
        app(GameSessionService::class)->checkpoint($session, [
            ...($session->state_snapshot ?? []),
            'prepared' => true,
            'paused' => false,
            'elapsed_seconds' => (int) data_get($session->state_snapshot, 'elapsed_seconds', 0),
        ]);
        app(HapticService::class)->trigger(HapticFeedback::Success);

        $this->replace(
            $this->route($this->gameDestination($session), ['session' => $session->getKey()]),
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

    private function loadSession(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $session = $this->session();

            if ($profile === null || $session === null || ! $this->hasSupportedRunner($session)) {
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

            $settings = app(SettingsService::class)->forProfile($profile);
            $workout = $session->workoutItem->workout;
            $items = $workout->items;
            $completedItems = $items->where('status', WorkoutStatus::Completed)->count();

            $this->gameTitle = $session->game->name;
            $this->gameOrder = 'Game '.$session->workoutItem->position.' of '.$items->count();
            $this->instructions = $this->instructionsFor($session);
            $this->gamesRemaining = max(0, $items->count() - $completedItems);
            $this->progress = $items->isEmpty() ? 0.0 : round($completedItems / $items->count(), 3);
            $this->timeEstimate = 'About '.app(WorkoutService::class)->estimatedGameDurationMinutes($session->level).' min';
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
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
            ])
            ->first();
    }

    private function instructionsFor(GameSession $session): string
    {
        return match ($session->game->type) {
            GameType::SignalShift => 'Watch the rule, keep your attention steady, and respond only when the target matches.',
            GameType::ClearThought => 'Read for meaning, favor the clearest choice, and take the time you need.',
        };
    }

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::Fade;
    }

    private function hasSupportedRunner(GameSession $session): bool
    {
        return match ($session->game->type) {
            GameType::SignalShift => ! $session->isFrameworkPlaceholder(),
            GameType::ClearThought => $session->isFrameworkPlaceholder(),
        };
    }

    private function gameDestination(GameSession $session): string
    {
        return $session->game->type === GameType::SignalShift
            ? 'native.workout.signal-shift'
            : 'native.workout.game';
    }
}
