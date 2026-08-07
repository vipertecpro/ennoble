<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Vertex\VertexGameService;
use App\Domain\Games\Vertex\VertexScoringService;
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
 * Vertex — a go/no-go game staged inside a tunnel. Objects rush out of a
 * vanishing point one at a time; the player strikes the ones matching the
 * current target form and lets every decoy fly past untouched. The target
 * re-keys as the run goes on, so the reflex the player just built becomes the
 * thing working against them.
 *
 * HOW THE MOTION WORKS. PHP cannot drive animation — the poll floor is 250ms
 * (4fps) and finger position never reaches PHP. So this screen owns only
 * discrete truth (which object is in flight, when its window opened, whether a
 * strike landed) while every frame of movement is a NATIVE tween: an object
 * mounts small at the vanishing point with a `scale` target and an
 * `animate-duration` equal to its flight, and the platform interpolates it to
 * full size at real framerate. The tunnel rings behind it are pure
 * `animate-loop` and never involve PHP at all.
 *
 * That split is also why the flight uses LINEAR easing: depth has to be a
 * straight function of elapsed time for the strike bonus to be honest, since
 * PHP scores depth from the clock rather than from anything the view reports.
 */
final class VertexGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#10B981';

    /** How often the flight clock ticks, in milliseconds. */
    private const TICK_MS = 250;

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#10B981',
        'on-accent' => '#04231A',
        'primary' => '#10B981',
        'on-primary' => '#04231A',
        'primary-surface' => '#10B9812E',
        'selected' => '#10B98140',
        'focus-ring' => '#10B98180',
    ];

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    /** ready | flight | result */
    public string $phase = 'ready';

    public int $readyTicks = 3;

    /** @var list<array{key: string, shape: string, is_go: bool}> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    /** The form the player must strike this round. */
    public string $targetShape = '';

    /** The form actually flying down the tunnel. */
    public string $objectShape = '';

    public bool $isGo = false;

    /** True when this round re-keyed the target from the previous round. */
    public bool $targetSwitched = false;

    public int $flightMs = 2100;

    public int $flightRemainingMs = 2100;

    public int $roundStartedAtMs = 0;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    /** idle | struck | false-alarm | passed | missed */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    /** Depth of the last strike, 0..1 — drives the "perfect strike" readout. */
    public float $lastDepth = 0.0;

    public int $lastDepthBonus = 0;

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
        return $this->view('screens.games.vertex.game');
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
     * Drive the ready countdown, the flight clock, and the reveal beat.
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
                $this->launch();
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

        $this->flightRemainingMs -= self::TICK_MS;

        if ($this->flightRemainingMs <= 0) {
            $this->resolvePass();
        }
    }

    /**
     * The player struck. The whole play area is the target — the object is
     * rushing at the camera, so demanding a hit on its exact bounds would be
     * testing dexterity rather than inhibition.
     */
    public function strike(): void
    {
        if (! $this->acceptsStrike()) {
            return;
        }

        $session = $this->session();
        $responseMs = $this->elapsedMs();
        $newCombo = $this->isGo ? $this->combo + 1 : 0;

        app(VertexGameService::class)->recordStrike(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            responseMs: $responseMs,
            flightMs: $this->flightMs,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->lastDepth = min(1.0, $responseMs / max(1, $this->flightMs));

        if ($this->isGo) {
            $this->lastDepthBonus = VertexScoringService::depthBonus($this->lastDepth);
            $this->feedbackTone = 'struck';
            $this->combo = $newCombo;
            $this->bestCombo = max($this->bestCombo, $newCombo);
            app(HapticService::class)->trigger(HapticFeedback::Success);
        } else {
            $this->lastDepthBonus = 0;
            $this->feedbackTone = 'false-alarm';
            $this->combo = 0;
            $this->lives = max(0, $this->lives - 1);
            app(HapticService::class)->trigger(HapticFeedback::Error);
        }

        $this->score = app(VertexGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
        $this->revealTicks = $this->reducedMotion ? 1 : 2;
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

        $this->replace('/play/vertex/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    /**
     * The object reached the camera untouched — correct for a decoy, a genuine
     * miss for a target.
     */
    private function resolvePass(): void
    {
        $session = $this->session();
        $newCombo = $this->isGo ? 0 : $this->combo + 1;

        app(VertexGameService::class)->recordPass(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            flightMs: $this->flightMs,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->lastDepthBonus = 0;

        if ($this->isGo) {
            $this->feedbackTone = 'missed';
            $this->combo = 0;
            $this->lives = max(0, $this->lives - 1);
            app(HapticService::class)->trigger(HapticFeedback::Error);
        } else {
            $this->feedbackTone = 'passed';
            $this->combo = $newCombo;
            $this->bestCombo = max($this->bestCombo, $newCombo);
            app(HapticService::class)->trigger(HapticFeedback::Selection);
        }

        $this->score = app(VertexGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
        $this->revealTicks = $this->reducedMotion ? 1 : 2;
    }

    private function advance(): void
    {
        if ($this->lives <= 0 || $this->roundIndex + 1 >= $this->totalRounds) {
            $this->finish();

            return;
        }

        $this->presentRound($this->roundIndex + 1);
        $this->launch();
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(VertexGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::Vertex) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/vertex');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(VertexGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->flightMs = max(800, (int) ($configuration['flight_ms'] ?? 2100));
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;

            $this->presentRound(0);
            $this->phase = 'ready';
            $this->readyTicks = $this->reducedMotion ? 2 : 4;
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function presentRound(int $index): void
    {
        $round = $this->rounds[$index];
        $previousTarget = $index > 0 ? (string) $this->rounds[$index - 1]['key'] : null;

        $this->roundIndex = $index;
        $this->targetShape = (string) $round['key'];
        $this->targetSwitched = $previousTarget !== null && $previousTarget !== $this->targetShape;
        $this->objectShape = (string) $round['shape'];
        $this->isGo = (bool) $round['is_go'];
        $this->feedbackTone = 'idle';
        $this->lastDepthBonus = 0;
        $this->awaitingAdvance = false;
        $this->revealTicks = 0;
    }

    /**
     * Open the flight window and send the object down the tunnel.
     */
    private function launch(): void
    {
        $this->phase = 'flight';
        $this->roundStartedAtMs = $this->nowMs();
        $this->flightRemainingMs = $this->flightMs;
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
     * Strikes only land on a live object, before its resolution beat.
     */
    private function acceptsStrike(): bool
    {
        return $this->phase === 'flight'
            && ! $this->awaitingAdvance
            && $this->feedbackTone === 'idle';
    }

    private function elapsedMs(): int
    {
        return max(1, min($this->flightMs, $this->nowMs() - $this->roundStartedAtMs));
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
