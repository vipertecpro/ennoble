<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\QuickMath\QuickMathGameService;
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
 * Quick Math — a timed, free-play mental-arithmetic game. Each round shows an
 * equation with a fill-in-the-blank answer slot; the player types the answer on
 * a transparent keypad before the round timer expires. Correct answers fill the
 * slot green, fire a confetti burst and build a combo; wrong answers and
 * time-outs turn the slot red and cost a life. Score/evidence are owned by
 * GameSessionService.
 */
final class QuickMathGame extends NativeComponent
{
    /** Hard cap on typed digits (the largest generated answer is 3 digits). */
    private const MAX_ANSWER_DIGITS = 4;

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    /** ready | playing | result */
    public string $phase = 'ready';

    public int $readyCountdown = 3;

    /** @var list<array{expression: string, answer: int, options: list<int>}> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    public string $expression = '';

    public int $answer = 0;

    /** The digits typed on the keypad for the current round. */
    public string $typedAnswer = '';

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    public int $secondsPerRound = 6;

    public int $secondsRemaining = 6;

    public int $roundStartedAtMs = 0;

    /** idle | correct | wrong | timeout */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    public bool $awaitingAdvance = false;

    /**
     * True while a wrong answer / time-out reveal waits for the player to tap
     * Continue. Correct answers auto-advance after a brief reward beat instead.
     */
    public bool $awaitingContinue = false;

    public int $revealTicks = 0;

    /** Seconds left before a wrong-answer / time-out reveal auto-continues. */
    public int $continueTicks = 0;

    public int $continueTotal = 3;

    /** Paused while the explanation screen is open, so it doesn't auto-advance. */
    public bool $continuePaused = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public int $feedbackMotionDuration = 0;

    public ?int $previousBest = null;

    public int $resultScore = 0;

    public ?float $resultAccuracy = null;

    public int $resultBestCombo = 0;

    public int $resultCorrect = 0;

    public bool $isNewBest = false;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding');

            return;
        }

        $this->loadSession();
    }

    public function render(): Element
    {
        return $this->view('screens.games.quick-math.game');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    /**
     * Drive the ready countdown and the per-round timer once per second.
     */
    #[Poll(1000)]
    public function tickGame(): void
    {
        if ($this->screenState !== 'content') {
            return;
        }

        if ($this->phase === 'ready') {
            $this->readyCountdown--;

            if ($this->readyCountdown <= 0) {
                $this->phase = 'playing';
                $this->secondsRemaining = $this->secondsPerRound;
                $this->roundStartedAtMs = $this->nowMs();
            }

            return;
        }

        if ($this->phase !== 'playing') {
            return;
        }

        if ($this->awaitingAdvance) {
            if ($this->awaitingContinue) {
                // Auto-continue after a short countdown (paused while the player
                // reads the explanation), so the reveal never stalls the game.
                if ($this->continuePaused) {
                    return;
                }

                $this->continueTicks--;

                if ($this->continueTicks <= 0) {
                    $this->awaitingContinue = false;
                    $this->advance();
                }

                return;
            }

            if ($this->revealTicks > 0) {
                $this->revealTicks--;

                return;
            }

            $this->advance();

            return;
        }

        $this->secondsRemaining--;

        if ($this->secondsRemaining <= 0) {
            $this->handleTimeout();
        }
    }

    /**
     * Append a digit to the typed answer from the numeric keypad.
     */
    public function pressKey(string $digit): void
    {
        if (! $this->acceptsInput()) {
            return;
        }

        if (! preg_match('/^[0-9]$/', $digit) || strlen($this->typedAnswer) >= self::MAX_ANSWER_DIGITS) {
            return;
        }

        $this->typedAnswer .= $digit;
        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Remove the last typed digit.
     */
    public function deleteKey(): void
    {
        if (! $this->acceptsInput() || $this->typedAnswer === '') {
            return;
        }

        $this->typedAnswer = substr($this->typedAnswer, 0, -1);
        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Resolve the current round from the typed answer.
     */
    public function submitAnswer(): void
    {
        if (! $this->acceptsInput() || $this->typedAnswer === '') {
            return;
        }

        $chosen = (int) $this->typedAnswer;

        $session = $this->session();
        $correct = $chosen === $this->answer;
        $responseMs = max(1, $this->nowMs() - $this->roundStartedAtMs);
        $newCombo = $correct ? $this->combo + 1 : 0;

        app(QuickMathGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            chosen: $chosen,
            responseMs: $responseMs,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;

        if ($correct) {
            $this->feedbackTone = 'correct';
            $this->combo = $newCombo;
            $this->bestCombo = max($this->bestCombo, $newCombo);
            app(HapticService::class)->trigger(HapticFeedback::Success);
            $this->revealTicks = 1;
        } else {
            $this->feedbackTone = 'wrong';
            $this->combo = 0;
            $this->lives = max(0, $this->lives - 1);
            app(HapticService::class)->trigger(HapticFeedback::Error);
            $this->startContinueCountdown();
        }

        $this->score = app(QuickMathGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
    }

    /**
     * Leave the wrong-answer / time-out reveal and move to the next round.
     */
    public function continueRound(): void
    {
        if (! $this->awaitingContinue) {
            return;
        }

        $this->awaitingContinue = false;
        $this->advance();
    }

    /**
     * Open the step-by-step explanation for the round just answered.
     */
    public function openExplain(): void
    {
        if (! $this->awaitingAdvance) {
            return;
        }

        // Freeze the auto-continue countdown while the explanation is open.
        $this->continuePaused = true;

        $this->navigate('/play/quick-math/'.$this->param('session').'/explain', [
            'expression' => $this->expression,
            'answer' => $this->answer,
        ])->transition($this->reducedMotion ? Transition::None : Transition::SlideFromBottom);
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

        $this->replace('/play/quick-math/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    /**
     * Leave the game for the games library.
     */
    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function onBackPressed(): void
    {
        $this->exit();
    }

    /**
     * Returning from the explanation restarts the auto-continue countdown so the
     * player gets a fresh few seconds rather than an instant advance.
     */
    public function onResume(): void
    {
        if ($this->awaitingContinue) {
            $this->continueTicks = $this->continueTotal;
            $this->continuePaused = false;
        }
    }

    private function handleTimeout(): void
    {
        $session = $this->session();

        app(QuickMathGameService::class)->recordTimeout(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'timeout';
        $this->typedAnswer = '';
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(QuickMathGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Warning);
        $this->awaitingAdvance = true;
        $this->startContinueCountdown();
    }

    private function startContinueCountdown(): void
    {
        $this->awaitingContinue = true;
        $this->continueTicks = $this->continueTotal;
        $this->continuePaused = false;
    }

    private function advance(): void
    {
        if ($this->lives <= 0 || $this->roundIndex + 1 >= $this->totalRounds) {
            $this->finish();

            return;
        }

        $this->presentRound($this->roundIndex + 1);
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(QuickMathGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::QuickMath) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/quick-math');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(QuickMathGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->secondsPerRound = max(3, (int) ($configuration['seconds_per_round'] ?? 6));
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;
            $this->readyCountdown = $this->reducedMotion ? 1 : 3;
            $this->phase = 'ready';

            $this->presentRound(0);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function presentRound(int $index): void
    {
        $round = $this->rounds[$index];

        $this->roundIndex = $index;
        $this->expression = $round['expression'];
        $this->answer = $round['answer'];
        $this->secondsRemaining = $this->secondsPerRound;
        $this->feedbackTone = 'idle';
        $this->typedAnswer = '';
        $this->awaitingAdvance = false;
        $this->awaitingContinue = false;
        $this->continuePaused = false;
        $this->continueTicks = 0;
        $this->roundStartedAtMs = $this->nowMs();
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
     * The keypad only accepts input while a round is actively awaiting an
     * answer — not during the ready countdown, the reveal, or the result.
     */
    private function acceptsInput(): bool
    {
        return $this->phase === 'playing'
            && ! $this->awaitingAdvance
            && $this->feedbackTone === 'idle';
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
