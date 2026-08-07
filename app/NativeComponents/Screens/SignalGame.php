<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Signal\SignalGameService;
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
 * Signal — an interference game. Each round shows a color name printed in a
 * different ink ("GREEN" in blue), and a rule banner that says which one counts:
 * INK (name what you see) or WORD (name what you read). The rule flips between
 * rounds, so the reflex you just built is the thing working against you.
 *
 * A correct call builds a combo and fires confetti; a wrong tap or a timed-out
 * round costs a life. The game carries its own amber accent, scoped to its
 * screens only so it never leaks into the rest of the app. Score/evidence are
 * owned by GameSessionService.
 */
final class SignalGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#F59E0B';

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#F59E0B',
        'on-accent' => '#000000',
        'primary' => '#F59E0B',
        'on-primary' => '#000000',
        'primary-surface' => '#F59E0B2E',
        'selected' => '#F59E0B40',
        'focus-ring' => '#F59E0B80',
    ];

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    /** ready | playing | result */
    public string $phase = 'ready';

    public int $readyCountdown = 3;

    /** @var list<array{rule: string, word: string, ink: string, answer: string, options: list<string>}> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    /** ink | word — which half of the stimulus counts this round. */
    public string $rule = 'ink';

    /** True when this round's rule flipped from the previous round's. */
    public bool $ruleSwitched = false;

    public string $word = '';

    public string $ink = '';

    public string $answer = '';

    /** @var list<string> */
    public array $options = [];

    public ?string $selectedOption = null;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    public int $secondsPerRound = 5;

    public int $secondsRemaining = 5;

    public int $roundStartedAtMs = 0;

    /** idle | correct | wrong | timeout */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    public bool $awaitingAdvance = false;

    public int $revealTicks = 0;

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
        return $this->view('screens.games.signal.game');
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
     * Drive the ready countdown, the per-round timer, and the reveal beat.
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
            // Hold the resolved round for a beat so the reveal reads before the
            // next stimulus — and so a rule flip is noticed, not blinked past.
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
     * Resolve the current round from the tapped color name.
     */
    public function chooseOption(string $value): void
    {
        if ($this->phase !== 'playing' || $this->awaitingAdvance || $this->feedbackTone !== 'idle') {
            return;
        }

        if (! in_array($value, $this->options, true)) {
            return;
        }

        $session = $this->session();
        $correct = $value === $this->answer;
        $responseMs = max(1, $this->nowMs() - $this->roundStartedAtMs);
        $newCombo = $correct ? $this->combo + 1 : 0;

        app(SignalGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            chosen: $value,
            responseMs: $responseMs,
            windowMs: $this->secondsPerRound * 1000,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->selectedOption = $value;
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

        $this->score = app(SignalGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
        $this->revealTicks = 1;
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

        $this->replace('/play/signal/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    private function handleTimeout(): void
    {
        $session = $this->session();

        app(SignalGameService::class)->recordTimeout(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            windowMs: $this->secondsPerRound * 1000,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'timeout';
        $this->selectedOption = null;
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(SignalGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Warning);

        $this->awaitingAdvance = true;
        $this->revealTicks = 1;
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
        $result = app(SignalGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::Signal) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/signal');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(SignalGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->secondsPerRound = max(3, (int) ($configuration['seconds_per_round'] ?? 5));
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
        $previousRule = $index > 0 ? (string) $this->rounds[$index - 1]['rule'] : null;

        $this->roundIndex = $index;
        $this->rule = (string) $round['rule'];
        $this->ruleSwitched = $previousRule !== null && $previousRule !== $this->rule;
        $this->word = (string) $round['word'];
        $this->ink = (string) $round['ink'];
        $this->answer = (string) $round['answer'];
        $this->options = array_values($round['options']);
        $this->selectedOption = null;
        $this->secondsRemaining = $this->secondsPerRound;
        $this->feedbackTone = 'idle';
        $this->awaitingAdvance = false;
        $this->revealTicks = 0;
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
