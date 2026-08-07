<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Vertex\VertexGameService;
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
 * Barrage — a Space Invaders shaped visual-search game. A formation descends
 * carrying invaders of mixed shape and colour, and a standing order says which
 * of them may be fired on. Clear every target before the formation lands and
 * hold fire on everything else.
 *
 * THE ROUND IS THE WAVE. The player solves a whole field at once, so a wave is
 * one unit of evidence: hits, false alarms and survivors are tallied here and
 * handed to VertexGameService when the wave resolves. Nothing is recorded per
 * tap — a lucky sweep should not read as skill.
 *
 * HOW THE MOTION WORKS. PHP cannot animate: the poll floor is 250ms and finger
 * position never reaches PHP. So this screen owns only discrete truth (which
 * invaders are alive, when the descent opened, what was struck) while the
 * descent itself is ONE native tween on the formation, and the starfield behind
 * it is a pure `animate-loop` that never involves PHP at all. Destroying an
 * invader removes a child from the formation without touching the formation's
 * own props, so the descent tween is never interrupted.
 */
final class VertexGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#10B981';

    /** How often the descent clock ticks, in milliseconds. */
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

    /** ready | wave | result */
    public string $phase = 'ready';

    public int $readyTicks = 3;

    /** @var list<array<string, mixed>> */
    public array $waves = [];

    public int $waveIndex = 0;

    public int $totalWaves = 0;

    public string $order = '';

    /** Invaders still standing this wave. @var list<array<string, mixed>> */
    public array $invaders = [];

    public int $descentMs = 6000;

    public int $descentRemainingMs = 6000;

    public int $waveStartedAtMs = 0;

    /** Tally for the wave in progress. */
    public int $hits = 0;

    public int $falseAlarms = 0;

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    /** idle | swept | breached | landed */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    /** Id of the last invader struck, so the view can flash it. */
    public int $lastStruck = -1;

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
        app(ThemeManager::class)->applyWithAccent(self::ACCENT_TOKENS);
    }

    public function onBackPressed(): void
    {
        $this->exit();
    }

    /**
     * Drive the ready countdown, the descent clock, and the reveal beat.
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
                $this->launchWave();
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

        $this->descentRemainingMs -= self::TICK_MS;

        if ($this->descentRemainingMs <= 0) {
            $this->resolveWave(landed: true);
        }
    }

    /**
     * Fire on one invader. A target is destroyed and removed from the
     * formation; a decoy is a false alarm and ends the wave immediately, so
     * the mistake cannot be buried under a fast clean-up.
     */
    public function fire(string $id): void
    {
        if (! $this->acceptsFire()) {
            return;
        }

        $invaderId = (int) $id;
        $struck = null;

        foreach ($this->invaders as $invader) {
            if ((int) $invader['id'] === $invaderId) {
                $struck = $invader;

                break;
            }
        }

        if ($struck === null) {
            return;
        }

        $this->lastStruck = $invaderId;
        $this->feedbackSerial++;

        if (($struck['is_target'] ?? false) !== true) {
            $this->falseAlarms++;
            app(HapticService::class)->trigger(HapticFeedback::Error);
            $this->resolveWave(landed: false);

            return;
        }

        $this->hits++;
        $this->invaders = array_values(array_filter(
            $this->invaders,
            static fn (array $invader): bool => (int) $invader['id'] !== $invaderId,
        ));
        app(HapticService::class)->trigger(HapticFeedback::Selection);

        if ($this->targetsRemaining() === 0) {
            $this->resolveWave(landed: false);
        }
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
     * Close out the wave and bank its evidence.
     */
    private function resolveWave(bool $landed): void
    {
        $session = $this->session();
        $survivors = $this->targetsRemaining();
        $remainingFraction = $landed
            ? 0.0
            : max(0, $this->descentRemainingMs) / max(1, $this->descentMs);
        $clean = $this->falseAlarms === 0 && $survivors === 0;
        $newCombo = $clean ? $this->combo + 1 : 0;

        app(VertexGameService::class)->recordWave(
            session: $session,
            wave: $this->waves[$this->waveIndex],
            hits: $this->hits,
            falseAlarms: $this->falseAlarms,
            survivors: $survivors,
            responseMs: $this->elapsedMs(),
            descentMs: $this->descentMs,
            remainingFraction: $remainingFraction,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;

        if ($clean) {
            $this->feedbackTone = 'swept';
            $this->combo = $newCombo;
            $this->bestCombo = max($this->bestCombo, $newCombo);
            app(HapticService::class)->trigger(HapticFeedback::Success);
        } else {
            $this->feedbackTone = $this->falseAlarms > 0 ? 'breached' : 'landed';
            $this->combo = 0;
            $this->lives = max(0, $this->lives - 1);
            app(HapticService::class)->trigger(HapticFeedback::Error);
        }

        $this->score = app(VertexGameService::class)->score($session)->score;
        $this->awaitingAdvance = true;
        $this->revealTicks = $this->reducedMotion ? 1 : 3;
    }

    private function advance(): void
    {
        if ($this->lives <= 0 || $this->waveIndex + 1 >= $this->totalWaves) {
            $this->finish();

            return;
        }

        $this->presentWave($this->waveIndex + 1);
        $this->launchWave();
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
            $this->waves = $service->wavesFor($session);
            $this->totalWaves = count($this->waves);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->descentMs = max(2000, (int) ($configuration['descent_ms'] ?? 6000));
            $this->maxLives = max(1, (int) ($configuration['lives'] ?? 3));
            $this->lives = $this->maxLives;

            $this->presentWave(0);
            $this->phase = 'ready';
            $this->readyTicks = $this->reducedMotion ? 2 : 4;
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function presentWave(int $index): void
    {
        $wave = $this->waves[$index];

        $this->waveIndex = $index;
        $this->order = (string) $wave['order'];
        $this->invaders = array_values($wave['invaders']);
        $this->hits = 0;
        $this->falseAlarms = 0;
        $this->lastStruck = -1;
        $this->feedbackTone = 'idle';
        $this->awaitingAdvance = false;
        $this->revealTicks = 0;
    }

    /**
     * Open the descent window and send the formation down.
     */
    private function launchWave(): void
    {
        $this->phase = 'wave';
        $this->waveStartedAtMs = $this->nowMs();
        $this->descentRemainingMs = $this->descentMs;
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
            'wave_index' => $this->waveIndex,
            'lives' => $this->lives,
            'combo' => $this->combo,
        ];
    }

    private function targetsRemaining(): int
    {
        return count(array_filter(
            $this->invaders,
            static fn (array $invader): bool => ($invader['is_target'] ?? false) === true,
        ));
    }

    /**
     * Shots only land on a live formation, before its resolution beat.
     */
    private function acceptsFire(): bool
    {
        return $this->phase === 'wave'
            && ! $this->awaitingAdvance
            && $this->feedbackTone === 'idle';
    }

    private function elapsedMs(): int
    {
        return max(1, min($this->descentMs, $this->nowMs() - $this->waveStartedAtMs));
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
