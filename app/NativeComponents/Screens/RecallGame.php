<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\Recall\RecallGameService;
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
 * Recall — a memory-sequence game. Each round a sequence of tiles flashes one by
 * one ("watch"), then the player taps them back in order ("recall"). The
 * sequence grows each round; a full match builds a combo and fires confetti, a
 * wrong tap costs a life. The game carries its own violet accent, scoped to its
 * screens only. Score/evidence are owned by GameSessionService.
 */
final class RecallGame extends NativeComponent
{
    /** The game's own accent, applied only while this screen is mounted. */
    public const ACCENT = '#8B5CF6';

    /**
     * @var array<string, string>
     */
    private const ACCENT_TOKENS = [
        'accent' => '#8B5CF6',
        'on-accent' => '#FFFFFF',
        'primary' => '#8B5CF6',
        'on-primary' => '#FFFFFF',
        'primary-surface' => '#8B5CF62E',
        'selected' => '#8B5CF640',
        'focus-ring' => '#8B5CF680',
    ];

    public string $screenState = 'content';

    public string $errorMessage = 'This game could not be started. Please try again.';

    /** ready | watch | recall | result */
    public string $phase = 'ready';

    public int $readyTicks = 2;

    /** @var list<array{sequence: list<int>}> */
    public array $rounds = [];

    public int $roundIndex = 0;

    public int $totalRounds = 0;

    public int $tiles = 9;

    /** @var list<int> */
    public array $sequence = [];

    /** Which step of the sequence is lit during playback (-1 = none). */
    public int $playbackStep = -1;

    /** @var list<int> Tiles the player has tapped so far this round. */
    public array $entered = [];

    public int $lives = 3;

    public int $maxLives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $score = 0;

    /** idle | correct | wrong */
    public string $feedbackTone = 'idle';

    public int $feedbackSerial = 0;

    public int $tapSerial = 0;

    public int $lastTile = -1;

    public bool $awaitingAdvance = false;

    public int $revealTicks = 0;

    public int $recallStartedAtMs = 0;

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
        return $this->view('screens.games.recall.game');
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
     * Drive the ready countdown, the sequence playback, and the reveal beat.
     */
    #[Poll(600)]
    public function tickGame(): void
    {
        if ($this->screenState !== 'content') {
            return;
        }

        if ($this->phase === 'ready') {
            $this->readyTicks--;

            if ($this->readyTicks <= 0) {
                $this->phase = 'watch';
                $this->playbackStep = -1;
            }

            return;
        }

        if ($this->phase === 'watch') {
            $this->playbackStep++;

            if ($this->playbackStep >= count($this->sequence)) {
                $this->phase = 'recall';
                $this->playbackStep = -1;
                $this->recallStartedAtMs = $this->nowMs();
            }

            return;
        }

        if ($this->phase === 'recall' && $this->awaitingAdvance) {
            if ($this->revealTicks > 0) {
                $this->revealTicks--;

                return;
            }

            $this->advance();
        }
    }

    /**
     * Register a tile tap during the recall phase.
     */
    public function tapTile(string $index): void
    {
        if (! $this->acceptsTap()) {
            return;
        }

        $tile = (int) $index;

        if ($tile < 0 || $tile >= $this->tiles) {
            return;
        }

        $this->entered[] = $tile;
        $this->lastTile = $tile;
        $this->tapSerial++;

        $position = count($this->entered) - 1;

        if (($this->sequence[$position] ?? -1) !== $tile) {
            $this->resolveWrong();

            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Selection);

        if (count($this->entered) === count($this->sequence)) {
            $this->resolveCorrect();
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

        $this->replace('/play/recall/'.$fresh->getKey())
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    public function exit(): void
    {
        $this->replace('/games')
            ->transition($this->reducedMotion ? Transition::None : Transition::Fade);
    }

    private function resolveCorrect(): void
    {
        $session = $this->session();
        $responseMs = max(1, $this->nowMs() - $this->recallStartedAtMs);
        $newCombo = $this->combo + 1;

        app(RecallGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            entered: $this->entered,
            responseMs: $responseMs,
            combo: $newCombo,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'correct';
        $this->combo = $newCombo;
        $this->bestCombo = max($this->bestCombo, $newCombo);
        $this->score = app(RecallGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Success);

        $this->awaitingAdvance = true;
        $this->revealTicks = 1;
    }

    private function resolveWrong(): void
    {
        $session = $this->session();
        $responseMs = max(1, $this->nowMs() - $this->recallStartedAtMs);

        app(RecallGameService::class)->recordAnswer(
            session: $session,
            round: $this->rounds[$this->roundIndex],
            entered: $this->entered,
            responseMs: $responseMs,
            combo: 0,
            stateSnapshot: $this->snapshot(),
        );

        $this->feedbackSerial++;
        $this->feedbackTone = 'wrong';
        $this->combo = 0;
        $this->lives = max(0, $this->lives - 1);
        $this->score = app(RecallGameService::class)->score($session)->score;
        app(HapticService::class)->trigger(HapticFeedback::Error);

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
        $result = app(RecallGameService::class)->complete($session);

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

            if ($session === null || $session->game->type !== GameType::Recall) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace('/games/recall');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Success);

            $service = app(RecallGameService::class);
            $this->rounds = $service->roundsFor($session);
            $this->totalRounds = count($this->rounds);
            $this->previousBest = $service->previousBestScore($session);

            $configuration = is_array($session->level->configuration) ? $session->level->configuration : [];
            $this->tiles = max(4, (int) ($configuration['tiles'] ?? 9));
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
        $this->sequence = array_values(array_map('intval', $round['sequence']));
        $this->entered = [];
        $this->playbackStep = -1;
        $this->lastTile = -1;
        $this->feedbackTone = 'idle';
        $this->awaitingAdvance = false;
        $this->revealTicks = 0;
        $this->phase = 'watch';
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
     * Tiles only accept taps during the recall phase, before a resolution.
     */
    private function acceptsTap(): bool
    {
        return $this->phase === 'recall'
            && ! $this->awaitingAdvance
            && $this->feedbackTone === 'idle';
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
