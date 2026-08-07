<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\Axis\AxisFigure;
use App\Domain\Games\Axis\AxisGameService;
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
use Vipertecpro\Scene3d\Scene\Camera;
use Vipertecpro\Scene3d\Scene\Material;
use Vipertecpro\Scene3d\Scene\Node;
use Vipertecpro\Scene3d\Scene\Scene;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Axis — mental rotation. The reference solid sits above two candidates; one is
 * that solid turned to a new angle, the other is its mirror image. Only one can
 * be rotated onto the original, and the player has to do that rotation in their
 * head to tell which.
 *
 * THIS IS THE GAME THAT NEEDS REAL 3D. Every other game in the app could be
 * drawn flat; this one cannot. A mirror image and a rotation are identical in
 * any single 2D projection — it is perspective, occlusion and shading that make
 * the difference readable at all. It is built on the scene3d plugin and is the
 * reason that plugin exists.
 *
 * PHP owns the geometry, not the frames. Each round's cube positions are
 * computed once as integer lattice coordinates and handed over as a scene; the
 * renderer holds them until the next round. Nothing here runs per frame.
 */
final class AxisGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#38BDF8';

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#38BDF8',
        'on-accent' => '#000000',
        'primary' => '#38BDF8',
        'on-primary' => '#000000',
        'primary-surface' => '#38BDF82E',
        'selected' => '#38BDF840',
        'focus-ring' => '#38BDF880',
    ];

    /** Where each figure sits in the scene. */
    private const REFERENCE_ORIGIN = [0.0, 2.6, 0.0];

    private const CANDIDATE_ORIGIN = [3.4, -2.4, 0.0];

    /**
     * Slightly under a full unit so touching cubes keep a visible seam. At
     * exactly 1.0 a figure fuses into one smooth blob and the shape becomes
     * far harder to read than the task intends.
     */
    private const CUBE_SCALE = 0.9;

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    public string $phase = 'ready';

    public int $readyCountdown = 3;

    /** @var list<array<string, mixed>> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    public string $answer = '';

    public ?string $selectedSide = null;

    public int $cubeCount = 0;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    public int $secondsPerRound = 16;

    public int $secondsRemaining = 16;

    public int $roundStartedAtMs = 0;

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
        return $this->view('screens.games.axis.game', [
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
     * A cube in the scene was tapped. The renderer reports the node's id, which
     * carries its figure as a prefix — "a:3" is the fourth cube of candidate A.
     * Tapping any cube of a figure is a vote for that whole figure, which is
     * what a player expects: they are choosing a solid, not a block.
     */
    public function chooseFigure(string $nodeId): void
    {
        $side = str_contains($nodeId, ':') ? strstr($nodeId, ':', true) : $nodeId;

        // The reference is tappable-looking but is not an answer, and stray
        // ids from a stale frame must not resolve a round.
        if (! in_array($side, ['a', 'b'], true)) {
            return;
        }

        if ($this->phase !== 'playing' || $this->awaitingAdvance || $this->feedbackTone !== 'idle') {
            return;
        }

        $session = $this->session();
        $correct = $side === $this->answer;
        $responseMs = max(1, $this->nowMs() - $this->roundStartedAtMs);
        $newCombo = $correct ? $this->combo + 1 : 0;

        app(AxisGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            chosen: $side,
            responseMs: $responseMs,
            windowMs: $this->secondsPerRound * 1000,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->selectedSide = $side;
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

        $this->score = app(AxisGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;

        // Two beats, not one: the reveal recolours both candidates, and the
        // player needs a moment to SEE which solid was the match. That is the
        // only feedback the task gives.
        $this->revealTicks = $this->reducedMotion ? 1 : 2;
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

        $this->replace('/play/axis/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    /**
     * The scene for the current round: the reference above, the two candidates
     * below it. Rebuilt on every render and diffed by the renderer, so an
     * unchanged round costs a node-id comparison rather than a reload.
     */
    private function scene(): Scene
    {
        $scene = Scene::make()
            ->background('#0B1220')
            ->camera((new Camera)->at(0, 0, 15)->lookAt(0, 0, 0));

        if ($this->screenState !== 'content' || $this->rounds === []) {
            return $scene;
        }

        $round = $this->rounds[$this->roundIndex];

        $scene = $this->addFigure($scene, 'ref', $round['cells'], self::REFERENCE_ORIGIN, $this->referenceMaterial(), tappable: false);

        [$aCells, $bCells] = $this->answer === 'a'
            ? [$round['matchCells'], $round['decoyCells']]
            : [$round['decoyCells'], $round['matchCells']];

        $scene = $this->addFigure($scene, 'a', $aCells, [-self::CANDIDATE_ORIGIN[0], self::CANDIDATE_ORIGIN[1], 0.0], $this->candidateMaterial('a'), tappable: true);

        return $this->addFigure($scene, 'b', $bCells, self::CANDIDATE_ORIGIN, $this->candidateMaterial('b'), tappable: true);
    }

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $cells
     * @param  array{0: float, 1: float, 2: float}  $origin
     */
    private function addFigure(Scene $scene, string $prefix, array $cells, array $origin, Material $material, bool $tappable): Scene
    {
        // Centred on the figure's own bounding box so a long solid does not
        // drift off to one side of its slot as its orientation changes.
        $centred = (new AxisFigure($cells))->centredCells();

        foreach ($centred as $index => $cell) {
            $node = Node::shape($prefix.':'.$index, Shapes::BOX)
                ->at($origin[0] + $cell[0], $origin[1] + $cell[1], $origin[2] + $cell[2])
                ->scale(self::CUBE_SCALE)
                ->material($material);

            $scene = $scene->add($tappable ? $node->tappable() : $node);
        }

        return $scene;
    }

    private function referenceMaterial(): Material
    {
        return Material::metal(self::ACCENT, roughness: 0.45);
    }

    /**
     * Candidates are neutral while the round is live — a colour difference
     * would be a cue the task must not give — and only separate on the reveal.
     */
    private function candidateMaterial(string $side): Material
    {
        if ($this->feedbackTone === 'idle') {
            return Material::metal('#94A3B8', roughness: 0.5);
        }

        if ($side === $this->answer) {
            return Material::glowing('#22C55E', 0.55);
        }

        return $side === $this->selectedSide
            ? Material::glowing('#EF4444', 0.5)
            : Material::metal('#475569', roughness: 0.6);
    }

    private function handleTimeout(): void
    {
        $session = $this->session();

        app(AxisGameService::class)->recordTimeout(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            windowMs: $this->secondsPerRound * 1000,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'timeout';
        $this->selectedSide = null;
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(AxisGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Warning);

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
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(AxisGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::Axis) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/axis');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(AxisGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->secondsPerRound = max(6, (int) ($configuration['seconds_per_round'] ?? 16));
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
        $this->answer = (string) $round['answer'];
        $this->cubeCount = count($round['cells']);
        $this->selectedSide = null;
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
