<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Stack\StackBoard;
use App\Domain\Games\Stack\StackGameService;
use App\Domain\Games\Stack\StackPieces;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Models\GameSession;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\ConsolePalette;
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
use Vipertecpro\Scene3d\Scene\Light;
use Vipertecpro\Scene3d\Scene\Material;
use Vipertecpro\Scene3d\Scene\Node;
use Vipertecpro\Scene3d\Scene\Scene;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Stack — falling blocks, rendered as real lit 3D cubes.
 *
 * WHY THIS GAME FITS AND THE RUNNER DID NOT. Everything here changes on a
 * DISCRETE tick. Gravity moves a piece one whole row at a time, several times
 * slower than PHP can think; a move is a whole cell; a rotation is a whole
 * quarter turn. Nothing is ever partway between two states, so there is no
 * continuous motion for PHP's model and the rendered picture to disagree
 * about — the failure that made an endless runner unplayable here cannot
 * occur. PHP is the entire game; the renderer only draws the board it is
 * handed.
 *
 * The gravity clock is deliberately independent of the poll. The poll wakes
 * the screen; the clock decides whether a row is due. That way the drop speed
 * is a game-design number rather than a consequence of how fast the framework
 * happens to re-render.
 */
final class StackGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#2DD4BF';

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#2DD4BF',
        'on-accent' => '#000000',
        'primary' => '#2DD4BF',
        'on-primary' => '#000000',
        'primary-surface' => '#2DD4BF2E',
        'selected' => '#2DD4BF40',
        'focus-ring' => '#2DD4BF80',
    ];

    public const COLUMNS = 8;

    public const ROWS = 16;

    /** World size of one cell, and the gap that keeps cubes reading separately. */
    private const CELL = 1.0;

    private const CUBE_SCALE = 0.92;

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    public string $phase = 'ready';

    public int $readyCountdown = 3;

    /** @var list<string> */
    public array $sequence = [];

    /**
     * The settled board as [row][column] => colour. Held as a plain array
     * because component state has to survive serialisation between renders;
     * {@see StackBoard} is rebuilt around it for every rule check.
     *
     * @var array<int, array<int, string|null>>
     */
    public array $cells = [];

    public int $pieceIndex = 0;

    public int $totalPieces = 0;

    public string $piece = '';

    public int $pieceColumn = 0;

    public int $pieceRow = 0;

    public int $pieceRotation = 0;

    /**
     * The next three pieces. Held as state rather than read from the sequence
     * in the view, so the preview cannot drift from what actually spawns.
     *
     * @var list<string>
     */
    public array $nextPieces = [];

    public int $level = 1;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    public int $lines = 0;

    /** The level's own pace, before the level ramp is applied to it. */
    public int $baseDropIntervalMs = 800;

    public int $dropIntervalMs = 800;

    public int $lastDropAtMs = 0;

    public int $pieceStartedAtMs = 0;

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
        return $this->view('screens.games.stack.game', [
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
     * Wake up and apply gravity if a row is actually due.
     *
     * The poll interval and the drop interval are separate on purpose: the
     * poll is how often the screen gets to think, the drop is how fast the
     * game is meant to be. Tying them together would make every speed a
     * multiple of the framework's cadence.
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
                $this->phase = 'playing';
                $this->lastDropAtMs = $this->nowMs();
                $this->pieceStartedAtMs = $this->nowMs();
            }

            return;
        }

        if ($this->phase !== 'playing') {
            return;
        }

        if ($this->nowMs() - $this->lastDropAtMs < $this->dropIntervalMs) {
            return;
        }

        $this->lastDropAtMs = $this->nowMs();
        $this->stepDown();
    }

    public function moveLeft(): void
    {
        $this->shift(-1);
    }

    public function moveRight(): void
    {
        $this->shift(1);
    }

    /**
     * Turn the piece, nudging it off a wall if that is what is blocking it.
     *
     * Without the nudge, a piece flush against a wall simply refuses to
     * rotate, which reads as the control being broken rather than as a rule.
     */
    public function rotate(): void
    {
        if ($this->phase !== 'playing') {
            return;
        }

        $board = $this->board();
        $next = $this->pieceRotation + 1;
        $cells = StackPieces::cells($this->piece, $next);

        foreach ([0, -1, 1, -2, 2] as $nudge) {
            if ($board->accepts($cells, $this->pieceColumn + $nudge, $this->pieceRow)) {
                $this->pieceRotation = $next;
                $this->pieceColumn += $nudge;
                app(HapticService::class)->trigger(HapticFeedback::Selection);

                return;
            }
        }
    }

    /** Send the piece straight down to where it would land. */
    public function hardDrop(): void
    {
        if ($this->phase !== 'playing') {
            return;
        }

        $board = $this->board();
        $cells = StackPieces::cells($this->piece, $this->pieceRotation);

        while ($board->accepts($cells, $this->pieceColumn, $this->pieceRow + 1)) {
            $this->pieceRow++;
        }

        $this->lastDropAtMs = $this->nowMs();
        $this->lockPiece();
    }

    /** One row down, by the player's choice rather than by gravity. */
    public function softDrop(): void
    {
        if ($this->phase !== 'playing') {
            return;
        }

        $this->lastDropAtMs = $this->nowMs();
        $this->stepDown();
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

        $this->replace('/play/stack/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    private function shift(int $direction): void
    {
        if ($this->phase !== 'playing') {
            return;
        }

        $cells = StackPieces::cells($this->piece, $this->pieceRotation);

        if ($this->board()->accepts($cells, $this->pieceColumn + $direction, $this->pieceRow)) {
            $this->pieceColumn += $direction;
        }
    }

    /** Gravity, or a soft drop: one row down, or settle where it is. */
    private function stepDown(): void
    {
        $cells = StackPieces::cells($this->piece, $this->pieceRotation);

        if ($this->board()->accepts($cells, $this->pieceColumn, $this->pieceRow + 1)) {
            $this->pieceRow++;

            return;
        }

        $this->lockPiece();
    }

    /**
     * Settle the piece, clear what it completed, and judge the placement.
     *
     * Holes are counted BEFORE and AFTER, against the board with rows already
     * cleared. Counting before the clear would blame a piece for cells that a
     * completed row was about to remove anyway.
     */
    private function lockPiece(): void
    {
        $before = $this->board();
        $cells = StackPieces::cells($this->piece, $this->pieceRotation);

        $locked = $before->lock($cells, $this->pieceColumn, $this->pieceRow, StackPieces::color($this->piece));
        [$settled, $linesCleared] = $locked->clearFullRows();

        $holesAdded = max(0, $settled->holeCount() - $before->holeCount());
        $clean = $holesAdded === 0;

        app(StackGameService::class)->recordPlacement(
            session: $this->session(),
            piece: $this->piece,
            linesCleared: $linesCleared,
            holesAdded: $holesAdded,
            thinkMs: max(1, $this->nowMs() - $this->pieceStartedAtMs),
            combo: $clean ? $this->combo + 1 : 0,
            stateSnapshot: $this->snapshot(),
        );

        $this->cells = $this->toArrayCells($settled);
        $this->lines += $linesCleared;
        $this->applyLevel();
        $this->feedbackSerial++;

        if ($clean) {
            $this->combo++;
            $this->bestCombo = max($this->bestCombo, $this->combo);
        } else {
            $this->combo = 0;
        }

        $this->feedbackTone = match (true) {
            $linesCleared >= 4 => 'quad',
            $linesCleared > 0 => 'clear',
            $clean => 'idle',
            default => 'buried',
        };

        if ($linesCleared > 0) {
            app(HapticService::class)->trigger(HapticFeedback::Success);
        }

        $this->score = app(StackGameService::class)->score($this->session())->score;

        // A piece that settles while still poking above the ceiling means the
        // stack has reached the top.
        if ($this->pieceRow < 0) {
            $this->topOut();

            return;
        }

        if ($this->pieceIndex + 1 >= $this->totalPieces) {
            $this->finish();

            return;
        }

        $this->spawn($this->pieceIndex + 1);
    }

    /**
     * The stack reached the ceiling. A life goes and the board is swept, which
     * keeps a bad opening from ending a session outright — this is a training
     * app, not an arcade cabinet.
     */
    private function topOut(): void
    {
        $this->lives = max(0, $this->lives - 1);
        $this->combo = 0;
        $this->feedbackTone = 'topped';
        $this->feedbackSerial++;
        app(HapticService::class)->trigger(HapticFeedback::Error);

        if ($this->lives <= 0 || $this->pieceIndex + 1 >= $this->totalPieces) {
            $this->finish();

            return;
        }

        $this->cells = $this->toArrayCells(StackBoard::empty(self::COLUMNS, self::ROWS));
        $this->spawn($this->pieceIndex + 1);
    }

    private function spawn(int $index): void
    {
        $this->pieceIndex = $index;
        $this->piece = $this->sequence[$index];
        $this->nextPieces = array_values(array_slice($this->sequence, $index + 1, 3));
        $this->pieceRotation = 0;
        $this->pieceColumn = (int) floor((self::COLUMNS - 4) / 2);
        // Entering one row above the board gives a tall piece somewhere to be
        // before its first drop, which is what makes a nearly-full board end
        // the run at the right moment rather than one piece early.
        $this->pieceRow = -1;
        $this->pieceStartedAtMs = $this->nowMs();
    }

    /**
     * The board and the live piece as lit cubes.
     *
     * Nothing here tweens. Every change is a whole cell, so the renderer
     * simply draws the board it is given — and a node whose cell did not
     * change keeps its revision and is skipped entirely.
     */
    private function scene(): Scene
    {
        $scene = Scene::make()
            // The screen's own colour, not the app's. This is a committed
            // look — see ConsolePalette — so the theme is not consulted.
            ->background(ConsolePalette::screen())
            // AMBIENT ONLY — no key light, so no shading gradient across a
            // face and no cast shadows at all. A falling-block board is read
            // as a grid of flat colours; shading every cube and dropping a
            // shadow under it made the board harder to read, not richer.
            ->lights(Light::ambient(48000.0))
            // Far away with a narrow field of view, which is how you fake an
            // orthographic camera without one: at this distance the frustum is
            // nearly parallel, so a cell at the edge of the board is the same
            // size and shape as one in the middle. Close up with a wide angle,
            // the outer columns splayed outward and the board looked like a
            // solid object seen in perspective.
            ->camera((new Camera)->at(0.0, 0.0, 62.0)->lookAt(0.0, 0.0, 0.0)->fieldOfView(15.2));

        if ($this->screenState !== 'content' || $this->cells === []) {
            return $scene;
        }

        foreach ($this->gridNodes() as $node) {
            $scene = $scene->add($node);
        }

        foreach ($this->board()->filledCells() as $cell) {
            $scene = $scene->add($this->cell(
                'c:'.$cell['row'].':'.$cell['column'],
                $cell['column'],
                $cell['row'],
                $cell['color'],
            ));
        }

        if ($this->phase !== 'playing' || $this->piece === '') {
            return $scene;
        }

        foreach (StackPieces::cells($this->piece, $this->pieceRotation) as $index => [$cellColumn, $cellRow]) {
            $row = $this->pieceRow + $cellRow;

            // The part of a piece still above the ceiling is simply not drawn.
            if ($row < 0) {
                continue;
            }

            $scene = $scene->add($this->cell(
                'p:'.$index,
                $this->pieceColumn + $cellColumn,
                $row,
                StackPieces::color($this->piece),
            ));
        }

        return $scene;
    }

    /**
     * The empty playfield behind the pieces: a dark backing panel with a line
     * on every cell boundary.
     *
     * Lines rather than 128 individual tiles. Both read the same on screen,
     * but a tile per cell would put well over a hundred extra nodes on the
     * wire on EVERY render, and the grid never changes.
     *
     * @return list<Node>
     */
    private function gridNodes(): array
    {
        $width = self::COLUMNS * self::CELL;
        $height = self::ROWS * self::CELL;

        $nodes = [
            Node::shape('grid:panel', Shapes::PLANE)
                ->at(0.0, 0.0, -0.75)
                ->size($width, $height, 1.0)
                ->material(Material::solid(ConsolePalette::screen())),
        ];

        // NOTE: opaque colours throughout. A translucent material needs blend
        // configuration in SceneKit; without it the alpha channel bleeds and a
        // faint teal came out brown on screen.
        //
        // A soft darkening at the top and bottom of the EMPTY grid. Four
        // bands of falling opacity rather than one, because a single band has
        // a hard edge and reads as a stripe.
        //
        // Behind the pieces, not in front. In front they dimmed the blocks
        // themselves, which was plainly wrong on the bottom rows — the fade is
        // meant to shade the playfield, not the game.
        //
        // And it is a THEMED colour, not black. Black over a light board does
        // not read as shadow, it reads as grey dirt.
        foreach ([[0.22, 1.2], [0.15, 2.2], [0.09, 3.4], [0.04, 4.8]] as $band => [$opacity, $depth]) {
            foreach ([1, -1] as $edge) {
                $nodes[] = Node::shape('grid:fade:'.$band.':'.($edge > 0 ? 't' : 'b'), Shapes::PLANE)
                    ->at(0.0, $edge * (($height - $depth) / 2), -0.4)
                    ->size($width, $depth, 1.0)
                    ->material(Material::solid(ConsolePalette::fade()))
                    ->opacity($opacity);
            }
        }

        // Interior boundaries only — the outer edge is the frame's. Kept
        // deliberately faint and thin: the grid is there to let a player judge
        // a column, not to be looked at. The frame is the bold element.
        for ($column = 1; $column < self::COLUMNS; $column++) {
            $nodes[] = Node::shape('grid:v'.$column, Shapes::PLANE)
                ->at(($column - self::COLUMNS / 2) * self::CELL, 0.0, -0.5)
                ->size(0.025, $height, 1.0)
                ->material(Material::solid(ConsolePalette::lineDim()));
        }

        for ($row = 1; $row < self::ROWS; $row++) {
            $nodes[] = Node::shape('grid:h'.$row, Shapes::PLANE)
                ->at(0.0, (self::ROWS / 2 - $row) * self::CELL, -0.5)
                ->size($width, 0.025, 1.0)
                ->material(Material::solid(ConsolePalette::lineDim()));
        }

        return $nodes;
    }

    /**
     * One cell, as a flat quad facing the camera.
     *
     * A PLANE, not a BOX. A box has side faces, and even with no shadows and
     * no key light the camera's remaining perspective reveals them toward the
     * edges of the board — which is exactly the "still looks 3D" that a
     * falling-block game must not have. A plane has one face and one normal,
     * so under a single ambient light it renders as one solid colour, edge to
     * edge, the same as any 2D game.
     */
    private function cell(string $id, int $column, int $row, string $color): Node
    {
        return Node::shape($id, Shapes::PLANE)
            ->at(
                ($column - (self::COLUMNS - 1) / 2) * self::CELL,
                ((self::ROWS - 1) / 2 - $row) * self::CELL,
                0.0,
            )
            ->scale(self::CUBE_SCALE)
            // Solid, not metal: a metal has no colour of its own and would
            // reflect the background, which is the whole point of the piece
            // palette being distinguishable.
            ->material(Material::solid($color));
    }

    /**
     * A level every ten rows cleared, each one quickening the drop.
     *
     * Progress here is measured in LINES, not pieces placed: a player who
     * clears efficiently should reach the fast game sooner than one who fills
     * the board with the same number of pieces.
     */
    private function applyLevel(): void
    {
        $this->level = 1 + intdiv($this->lines, 10);

        // Floored well above the poll interval — a drop faster than the screen
        // can think would land pieces between frames.
        $this->dropIntervalMs = max(280, $this->baseDropIntervalMs - (($this->level - 1) * 60));
    }

    private function board(): StackBoard
    {
        return new StackBoard(self::COLUMNS, self::ROWS, $this->cells);
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function toArrayCells(StackBoard $board): array
    {
        $rows = array_fill(0, self::ROWS, array_fill(0, self::COLUMNS, null));

        foreach ($board->filledCells() as $cell) {
            $rows[$cell['row']][$cell['column']] = $cell['color'];
        }

        return $rows;
    }

    private function finish(): void
    {
        $session = $this->session();
        $result = app(StackGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::Stack) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/stack');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(StackGameService::class);
            $this->sequence = $service->sequenceFor($session);
            $this->totalPieces = count($this->sequence);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->baseDropIntervalMs = max(280, (int) ($configuration['drop_ms'] ?? 800));
            $this->dropIntervalMs = $this->baseDropIntervalMs;
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;
            $this->readyCountdown = $this->reducedMotion ? 1 : 3;
            $this->phase = 'ready';
            $this->cells = $this->toArrayCells(StackBoard::empty(self::COLUMNS, self::ROWS));

            $this->spawn(0);
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
            'piece_index' => $this->pieceIndex,
            'lives' => $this->lives,
            'combo' => $this->combo,
            'lines' => $this->lines,
        ];
    }

    private function nowMs(): int
    {
        return (int) now()->getTimestampMs();
    }
}
