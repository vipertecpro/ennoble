<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\Flow\FlowGameService;
use App\Domain\Games\GameSessionService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\GameType;
use App\Enums\SessionStatus;
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

/**
 * Flow — "ride the current." Each round a current surges toward the player's
 * light carrying a direction; the player swipes with it before it arrives. A
 * matched swipe flows through (combo + confetti), a wrong swipe or a current
 * that reaches un-swiped costs a life. The game carries its own indigo accent,
 * scoped to its screens only, and plays over a living aurora field.
 *
 * The interaction is swipe-only: the platform's continuous pan never reaches
 * PHP, so a discrete `@swipe` (with a poll-driven deadline) is the reliable
 * input. Score/evidence are owned by GameSessionService.
 */
final class FlowGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#6366F1';

    /** How often the deadline clock ticks, in milliseconds. */
    private const TICK_MS = 250;

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#6366F1',
        'on-accent' => '#FFFFFF',
        'primary' => '#6366F1',
        'on-primary' => '#FFFFFF',
        'primary-surface' => '#6366F12E',
        'selected' => '#6366F140',
        'focus-ring' => '#6366F180',
    ];

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    /** ready | flow | result */
    public string $phase = 'ready';

    public int $readyTicks = 2;

    /** @var list<array{direction: string}> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    public string $currentDirection = '';

    public int $windowMs = 2000;

    public int $windowRemainingMs = 2000;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    /** idle | correct | wrong | timeout */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    public bool $awaitingAdvance = false;

    public int $revealTicks = 0;

    public int $roundStartedAtMs = 0;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public int $feedbackMotionDuration = 0;

    public string $accentColor = self::ACCENT;

    public ?int $previousBest = null;

    public int $resultScore = 0;

    public ?float $resultAccuracy = null;

    public int $resultBestCombo = 0;

    public int $resultCorrect = 0;

    public bool $isNewBest = false;

    public function mount(): void
    {
        app(ThemeManager::class)->applyWithAccent(self::ACCENT_TOKENS);

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding');

            return;
        }

        $this->loadSession();
    }

    public function render(): Element
    {
        return $this->view('screens.games.flow.game');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function onResume(): void
    {
        // Re-assert the game's accent in case another screen reset the theme.
        app(ThemeManager::class)->applyWithAccent(self::ACCENT_TOKENS);
    }

    public function onBackPressed(): void
    {
        $this->exit();
    }

    /**
     * Drive the ready countdown, the current's deadline, and the reveal beat.
     */
    #[Poll(self::TICK_MS)]
    public function tickGame(): void
    {
        if ($this->screenState !== 'content' || $this->phase === 'result') {
            return;
        }

        if ($this->phase === 'ready') {
            $this->readyTicks--;

            if ($this->readyTicks <= 0) {
                $this->startWindow();
            }

            return;
        }

        if ($this->awaitingAdvance) {
            if ($this->revealTicks > 0) {
                $this->revealTicks--;

                return;
            }

            $this->advance();

            return;
        }

        $this->windowRemainingMs -= self::TICK_MS;

        if ($this->windowRemainingMs <= 0) {
            $this->resolveTimeout();
        }
    }

    /**
     * React to a swipe. During a live current a matching direction flows
     * through; a mismatch breaks it. Swipes are ignored outside a live current.
     */
    public function handleSwipe(string $direction): void
    {
        if (! $this->acceptsSwipe()) {
            return;
        }

        if (! in_array($direction, ['left', 'right', 'up', 'down'], true)) {
            return;
        }

        if ($direction === $this->currentDirection) {
            $this->resolveCorrect($direction);

            return;
        }

        $this->resolveWrong($direction);
    }

    /**
     * Restart the game as a brand-new free-play session.
     */
    public function playAgain(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null) {
            $this->replace('/games');

            return;
        }

        $session = $this->session();
        $fresh = app(GameSessionService::class)->startFreePlay($profile, $session->game, $session->level);

        $this->replace('/play/flow/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    private function resolveCorrect(string $direction): void
    {
        $session = $this->session();
        $responseMs = $this->elapsedMs();
        $newCombo = $this->combo + 1;

        app(FlowGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            swiped: $direction,
            responseMs: $responseMs,
            windowMs: $this->windowMs,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'correct';
        $this->combo = $newCombo;
        $this->bestCombo = max($this->bestCombo, $newCombo);
        $this->score = app(FlowGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Success);

        $this->awaitingAdvance = true;
        $this->revealTicks = $this->reducedMotion ? 1 : 2;
    }

    private function resolveWrong(string $direction): void
    {
        $session = $this->session();

        app(FlowGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            swiped: $direction,
            responseMs: $this->elapsedMs(),
            windowMs: $this->windowMs,
            combo: 0,
            stateSnapshot: $this->snapshot(),
        );

        $this->registerMiss($session, 'wrong');
    }

    private function resolveTimeout(): void
    {
        $session = $this->session();

        app(FlowGameService::class)->recordTimeout(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            windowMs: $this->windowMs,
            stateSnapshot: $this->snapshot(),
        );

        $this->registerMiss($session, 'timeout');
    }

    private function registerMiss(GameSession $session, string $tone): void
    {
        $this->feedbackSerial++;
        $this->feedbackTone = $tone;
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(FlowGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Error);

        $this->awaitingAdvance = true;
        $this->revealTicks = $this->reducedMotion ? 1 : 3;
    }

    private function advance(): void
    {
        if ($this->lives <= 0 || $this->roundIndex + 1 >= $this->totalRounds) {
            $this->finish();

            return;
        }

        $this->presentRound($this->roundIndex + 1);
        $this->startWindow();
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(FlowGameService::class)->complete($session);

        $this->resultScore = $result->score;
        $this->resultAccuracy = $result->accuracy;
        $this->resultBestCombo = $result->bestCombo;
        $this->resultCorrect = $result->correctCount;
        $this->isNewBest = $this->previousBest === null
            ? $result->score > 0
            : $result->score > $this->previousBest;
        $this->phase = 'result';
        $this->feedbackTone = 'idle';
        app(HapticService::class)->trigger(HapticFeedback::Success);
    }

    private function loadSession(): void
    {
        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                $this->replace('/onboarding');

                return;
            }

            $session = GameSession::query()
                ->with(['game', 'level', 'profile'])
                ->whereBelongsTo($profile)
                ->find((int) $this->param('session'));

            if ($session === null || $session->game->type !== GameType::Flow) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/flow');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(FlowGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->windowMs = max(600, (int) ($configuration['window_ms'] ?? 2000));
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;

            $this->presentRound(0);
            $this->phase = 'ready';
            $this->readyTicks = $this->reducedMotion ? 1 : 2;
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function presentRound(int $index): void
    {
        $round = $this->rounds[$index];

        $this->roundIndex = $index;
        $this->currentDirection = (string) $round['direction'];
        $this->feedbackTone = 'idle';
        $this->awaitingAdvance = false;
        $this->revealTicks = 0;
    }

    /**
     * Open the deadline window for the presented round and launch its current.
     */
    private function startWindow(): void
    {
        $this->phase = 'flow';
        $this->roundStartedAtMs = $this->nowMs();
        $this->windowRemainingMs = $this->windowMs;
        $this->feedbackTone = 'idle';
        $this->feedbackSerial++;
    }

    private function session(): GameSession
    {
        $profile = app(ProfileService::class)->current();

        return GameSession::query()
            ->with(['game', 'level', 'profile'])
            ->whereBelongsTo($profile)
            ->findOrFail((int) $this->param('session'));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'round_index' => $this->roundIndex,
            'lives' => $this->lives,
            'combo' => $this->combo,
        ];
    }

    /**
     * Swipes only resolve a live current, before its resolution beat.
     */
    private function acceptsSwipe(): bool
    {
        return $this->phase === 'flow'
            && ! $this->awaitingAdvance
            && $this->feedbackTone === 'idle';
    }

    private function elapsedMs(): int
    {
        return max(1, min($this->windowMs, $this->nowMs() - $this->roundStartedAtMs));
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
