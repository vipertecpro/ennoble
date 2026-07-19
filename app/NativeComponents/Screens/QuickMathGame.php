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
 * equation and four numeric options; the player taps the correct answer before
 * the round timer expires. Correct answers build a combo and score; wrong
 * answers and time-outs cost a life. Score/evidence are owned by
 * GameSessionService.
 */
final class QuickMathGame extends NativeComponent
{
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

    /** @var list<int> */
    public array $options = [];

    public int $answer = 0;

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

    public ?int $selectedOption = null;

    public bool $awaitingAdvance = false;

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
        return $this->view('screens.quick-math-game');
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
            $this->advance();

            return;
        }

        $this->secondsRemaining--;

        if ($this->secondsRemaining <= 0) {
            $this->handleTimeout();
        }
    }

    /**
     * Resolve the current round from the tapped answer tile.
     */
    public function chooseOption(string $value): void
    {
        if ($this->phase !== 'playing' || $this->awaitingAdvance || $this->feedbackTone !== 'idle') {
            return;
        }

        $chosen = (int) $value;

        if (! in_array($chosen, $this->options, true)) {
            return;
        }

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

        $this->selectedOption = $chosen;
        $this->feedbackSerial++;

        if ($correct) {
            $this->feedbackTone = 'correct';
            $this->combo = $newCombo;
            $this->bestCombo = max($this->bestCombo, $newCombo);
            app(HapticService::class)->trigger(HapticFeedback::Success);
        } else {
            $this->feedbackTone = 'wrong';
            $this->combo = 0;
            $this->lives = max(0, $this->lives - 1);
            app(HapticService::class)->trigger(HapticFeedback::Error);
        }

        $this->score = app(QuickMathGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
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
     * Leave the game for the games library (which carries the tab bar, so the
     * player can reach Home again).
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
     * Swipe right to leave — a flick back out of the game. Ignored while a
     * round is live so a fast horizontal tap streak can't drop the player out.
     */
    public function handleSwipe(string $direction): void
    {
        if ($direction === 'right' && $this->phase !== 'playing') {
            $this->exit();
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
        $this->selectedOption = null;
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(QuickMathGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Warning);
        $this->awaitingAdvance = true;
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
        $this->options = $round['options'];
        $this->answer = $round['answer'];
        $this->secondsRemaining = $this->secondsPerRound;
        $this->feedbackTone = 'idle';
        $this->selectedOption = null;
        $this->awaitingAdvance = false;
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

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
