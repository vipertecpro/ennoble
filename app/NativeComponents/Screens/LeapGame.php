<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Leap\LeapGameService;
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
use Vipertecpro\Scene3d\Scene\Camera;
use Vipertecpro\Scene3d\Scene\Material;
use Vipertecpro\Scene3d\Scene\Node;
use Vipertecpro\Scene3d\Scene\Scene;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Leap — an endless-runner in the shape of the Dinosaur Game. Obstacles come at
 * you, you tap to jump, one clean run is as long as your timing holds. No
 * clock: the run ends when the course does or the lives do.
 *
 * HOW THIS WORKS WITHOUT PHP DRAWING FRAMES. PHP cannot animate — the poll
 * floor is ~250ms — so it never moves anything. It hands each obstacle to the
 * renderer once, with a destination and a duration, and the render thread
 * tweens it. Rebuilding the identical node next poll produces the identical
 * revision, so the diff skips it and the tween is never interrupted; that
 * property is the only reason this genre is possible here at all.
 *
 * COLLISION IS ARITHMETIC, NOT A PER-FRAME TEST. PHP knows when it handed each
 * obstacle over and how long it takes to cross, so it knows exactly when the
 * obstacle reaches the runner. A jump is an interval. Clearing an obstacle is
 * those two overlapping — resolved after the fact, which is why a 250ms tick
 * can adjudicate a 600ms window without ever sampling positions.
 */
final class LeapGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#F97316';

    /** How long after the tap the runner is off the ground at all. */
    public const CLEAR_WINDOW_START_MS = 90;

    /**
     * ...and when it lands. The generator's minimum gap must stay above this,
     * or one jump would clear two obstacles.
     */
    public const CLEAR_WINDOW_END_MS = 610;

    /** The full jump, including the beat after landing before another is allowed. */
    private const AIRBORNE_MS = 700;

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#F97316',
        'on-accent' => '#000000',
        'primary' => '#F97316',
        'on-primary' => '#000000',
        'primary-surface' => '#F973162E',
        'selected' => '#F9731640',
        'focus-ring' => '#F9731680',
    ];

    /** Where the runner stands, and where obstacles enter and leave. */
    private const RUNNER_X = -3.2;

    private const SPAWN_X = 11.0;

    private const EXIT_X = -11.0;

    private const GROUND_Y = -1.2;

    /** Peak of the jump arc, in world units above the runner's resting height. */
    private const JUMP_HEIGHT = 2.3;

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    public string $phase = 'ready';

    public int $readyCountdown = 3;

    /** @var list<array{gap_ms: int, travel_ms: int, height: int}> */
    public array $course = [];

    /**
     * Obstacles handed to the renderer and not yet resolved.
     *
     * @var list<array{index: int, spawned_at_ms: int, arrives_at_ms: int, exits_at_ms: int, travel_ms: int, height: int}>
     */
    public array $active = [];

    public int $nextSpawnIndex = 0;

    public int $resolvedCount = 0;

    public int $totalObstacles = 0;

    /** When the next obstacle is due to be handed over, in run time. */
    public int $nextSpawnAtMs = 0;

    public int $runStartedAtMs = 0;

    public int $jumpStartedAtMs = 0;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

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
        return $this->view('screens.games.leap.game', [
            'scene' => $this->scene()->toArray(),
        ]);
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
        app(ThemeManager::class)->applyWithAccent(self::ACCENT_TOKENS);
    }

    public function onBackPressed(): void
    {
        $this->exit();
    }

    /**
     * The run's clock. Hands over obstacles that are due, resolves the ones
     * that have reached the runner, and ends the run when the course or the
     * lives are spent.
     */
    #[Poll(250)]
    public function tickGame(): void
    {
        if ($this->screenState !== 'content') {
            return;
        }

        if ($this->phase === 'ready') {
            $this->readyCountdown--;

            if ($this->readyCountdown <= 0) {
                $this->phase = 'running';
                $this->runStartedAtMs = $this->nowMs();
            }

            return;
        }

        if ($this->phase !== 'running') {
            return;
        }

        $now = $this->nowMs();

        $this->spawnDue($now);
        $this->resolveArrived($now);
        $this->retireExited($now);

        if ($this->lives <= 0 || ($this->resolvedCount >= $this->totalObstacles && $this->totalObstacles > 0)) {
            $this->finish();
        }
    }

    /**
     * Jump. The tap carries no position and needs none — there is one action.
     */
    public function jump(): void
    {
        if ($this->phase !== 'running') {
            return;
        }

        $now = $this->nowMs();

        // Already airborne: ignore rather than queue. Letting a second tap
        // extend the jump would make mashing strictly better than timing.
        if ($this->jumpStartedAtMs > 0 && $now - $this->jumpStartedAtMs < self::AIRBORNE_MS) {
            return;
        }

        $this->jumpStartedAtMs = $now;
        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    public function playAgain(): void
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null) {
            $this->replace('/games');

            return;
        }

        $session = $this->session();
        $fresh = app(GameSessionService::class)->startFreePlay($profile, $session->game, $session->level);

        $this->replace('/play/leap/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    /**
     * Hand over every obstacle whose turn has come.
     *
     * The spawn time is recorded as the moment PHP ACTUALLY hands it over, not
     * the moment the course said it was due. The two differ by up to one poll,
     * and taking the scheduled time would leave the collision arithmetic
     * describing an obstacle a quarter-second away from the one on screen.
     */
    private function spawnDue(int $now): void
    {
        while ($this->nextSpawnIndex < $this->totalObstacles
            && $now - $this->runStartedAtMs >= $this->nextSpawnAtMs) {
            $obstacle = $this->course[$this->nextSpawnIndex];
            $travelMs = max(1, (int) $obstacle['travel_ms']);

            $this->active[] = [
                'index' => $this->nextSpawnIndex,
                'spawned_at_ms' => $now,
                'arrives_at_ms' => $now + (int) round($travelMs * $this->arriveFraction()),
                'exits_at_ms' => $now + $travelMs,
                'travel_ms' => $travelMs,
                'height' => (int) $obstacle['height'],
            ];

            $this->nextSpawnIndex++;

            if ($this->nextSpawnIndex < $this->totalObstacles) {
                $this->nextSpawnAtMs += (int) $this->course[$this->nextSpawnIndex]['gap_ms'];
            }
        }
    }

    /**
     * Adjudicate every obstacle that has reached the runner since the last
     * tick. Resolving after the fact is what lets a 250ms tick judge a 600ms
     * window without ever sampling a position.
     */
    private function resolveArrived(int $now): void
    {
        foreach ($this->active as $slot => $obstacle) {
            if ($obstacle['arrives_at_ms'] > $now || isset($obstacle['resolved'])) {
                continue;
            }

            $leadMs = $this->jumpStartedAtMs > 0
                ? $obstacle['arrives_at_ms'] - $this->jumpStartedAtMs
                : null;

            $cleared = $leadMs !== null
                && $leadMs >= self::CLEAR_WINDOW_START_MS
                && $leadMs <= self::CLEAR_WINDOW_END_MS;

            app(LeapGameService::class)->recordObstacle(
                session: $this->session(),
                obstacle: $this->course[$obstacle['index']],
                cleared: $cleared,
                leadMs: $leadMs,
                combo: $cleared ? $this->combo + 1 : 0,
                stateSnapshot: $this->snapshot(),
            );

            $this->active[$slot]['resolved'] = true;
            $this->resolvedCount++;
            $this->feedbackSerial++;

            if ($cleared) {
                $this->combo++;
                $this->bestCombo = max($this->bestCombo, $this->combo);
                $this->feedbackTone = 'cleared';
            } else {
                $this->combo = 0;
                $this->lives = max(0, $this->lives - 1);
                $this->feedbackTone = 'hit';
                app(HapticService::class)->trigger(HapticFeedback::Error);
            }

            $this->score = app(LeapGameService::class)->score($this->session())->score;
        }
    }

    /** Drop obstacles that have left the viewport so the scene stays small. */
    private function retireExited(int $now): void
    {
        $this->active = array_values(array_filter(
            $this->active,
            static fn (array $obstacle): bool => $obstacle['exits_at_ms'] > $now,
        ));
    }

    /**
     * The fraction of an obstacle's crossing that is spent reaching the runner.
     * Constant, because every obstacle travels the same span.
     */
    private function arriveFraction(): float
    {
        return (self::SPAWN_X - self::RUNNER_X) / (self::SPAWN_X - self::EXIT_X);
    }

    /**
     * The scene: ground, runner, and every live obstacle.
     *
     * Every node here is rebuilt from the same calls on every render, which
     * yields the same revision and lets the renderer skip it — that is what
     * keeps the obstacle tweens running smoothly across a 250ms poll.
     */
    private function scene(): Scene
    {
        // The viewport takes the app's OWN surface colour rather than a dark
        // one of its own, so it reads as part of the page instead of a hole
        // punched in it — and it follows light and dark without a second
        // palette. theme() resolves the token to hex at render time; the
        // renderer also uses this as the sky its metals reflect, so the whole
        // scene re-tints with the app.
        $scene = Scene::make()
            ->background(theme('surface'))
            ->camera((new Camera)->at(0.0, 1.1, 13.5)->lookAt(0.0, 0.6, 0.0));

        if ($this->screenState !== 'content') {
            return $scene;
        }

        $scene = $scene->add(
            Node::shape('ground', Shapes::BOX)
                ->at(0.0, self::GROUND_Y - 0.5, 0.0)
                ->size(26.0, 1.0, 5.0)
                ->material(Material::metal(theme('surface-variant'), roughness: 0.75)),
            $this->runnerNode(),
        );

        foreach ($this->active as $obstacle) {
            $height = max(1, $obstacle['height']);

            $scene = $scene->add(
                Node::shape('ob:'.$obstacle['index'], Shapes::BOX)
                    ->at(self::SPAWN_X, self::GROUND_Y + ($height * 0.5), 0.0)
                    ->size(0.9, $height * 1.0, 0.9)
                    ->material(Material::metal(self::ACCENT, roughness: 0.35))
                    // Handed over once. The renderer owns the motion from here.
                    ->moveTo(self::EXIT_X, self::GROUND_Y + ($height * 0.5), 0.0, $obstacle['travel_ms'] / 1000),
            );
        }

        return $scene;
    }

    /**
     * The runner, at rest or mid-jump.
     *
     * The arc is two halves, chosen by where the jump is in its own window,
     * because a node can only be given ONE destination at a time — there is no
     * way to express "up then down" in a single move.
     */
    private function runnerNode(): Node
    {
        $node = Node::shape('runner', Shapes::CAPSULE)
            ->size(0.85, 1.15, 0.85)
            // on-surface is the token that is dark on a light theme and light
            // on a dark one, so the runner cannot vanish into the ground.
            ->material(Material::metal(theme('on-surface'), roughness: 0.3));

        $elapsed = $this->jumpStartedAtMs > 0 ? $this->nowMs() - $this->jumpStartedAtMs : PHP_INT_MAX;

        if ($elapsed >= self::AIRBORNE_MS) {
            return $node->at(self::RUNNER_X, self::GROUND_Y + 0.6, 0.0);
        }

        $apexMs = (int) (self::AIRBORNE_MS / 2);

        if ($elapsed < $apexMs) {
            return $node
                ->at(self::RUNNER_X, self::GROUND_Y + 0.6, 0.0)
                ->moveTo(self::RUNNER_X, self::GROUND_Y + 0.6 + self::JUMP_HEIGHT, 0.0, ($apexMs - $elapsed) / 1000);
        }

        return $node
            ->at(self::RUNNER_X, self::GROUND_Y + 0.6 + self::JUMP_HEIGHT, 0.0)
            ->moveTo(self::RUNNER_X, self::GROUND_Y + 0.6, 0.0, (self::AIRBORNE_MS - $elapsed) / 1000);
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(LeapGameService::class)->complete($session);

        $this->resultScore = $result->score;
        $this->resultAccuracy = $result->accuracy;
        $this->resultBestCombo = $result->bestCombo;
        $this->resultCorrect = $result->correctCount;
        $this->isNewBest = $this->previousBest === null
            ? $result->score > 0
            : $result->score > $this->previousBest;
        $this->phase = 'result';
        $this->feedbackTone = 'idle';
        $this->active = [];
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

            if ($session === null || $session->game->type !== GameType::Leap) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/leap');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(LeapGameService::class);
            $this->course = $service->courseFor($session);
            $this->totalObstacles = count($this->course);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;
            $this->readyCountdown = $this->reducedMotion ? 1 : 3;
            $this->phase = 'ready';
            $this->nextSpawnAtMs = $this->course === [] ? 0 : (int) $this->course[0]['gap_ms'];
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
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
            'resolved' => $this->resolvedCount,
            'lives' => $this->lives,
            'combo' => $this->combo,
        ];
    }

    /**
     * The run's clock. Read through Carbon rather than microtime so a test can
     * travel it: every timing rule in this game — the spawn schedule, the
     * arrival, the jump window — is a comparison against this, and none of it
     * is checkable if the only way to advance time is to actually wait.
     */
    private function nowMs(): int
    {
        return (int) now()->getTimestampMs();
    }
}
