<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Enums\Difficulty;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\Statistic;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Str;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

/**
 * Game detail — the "how to play" screen shown when a game tile is tapped.
 * Presents the tagline, the rules, the skills it trains, and the player's best
 * score, then launches a fresh free-play session from the Play button.
 */
final class GameDetail extends NativeComponent
{
    /**
     * Per-game guidance. Keyed by slug so one screen serves every game tile.
     */
    private const GUIDES = [
        'word-match' => [
            'tagline' => 'Match each word to its meaning before the timer runs out.',
            'steps' => [
                'A prompt word appears with a relation — synonym or antonym.',
                'Tap the option that matches before the round timer expires.',
                'Fast, unbroken answers build a combo for bonus points.',
                'You have three lives — a wrong answer or a time-out costs one.',
            ],
        ],
        'quick-math' => [
            'tagline' => 'Solve fast-fire arithmetic and keep your streak alive.',
            'steps' => [
                'An equation appears with four possible answers.',
                'Tap the correct answer before the round timer expires.',
                'Quick, correct streaks multiply your score.',
                'You have three lives — a wrong answer or a time-out costs one.',
            ],
        ],
        'recall' => [
            'tagline' => 'Watch the sequence light up, then tap it back from memory.',
            'steps' => [
                'A sequence of tiles flashes one by one — watch closely.',
                'Tap the tiles back in the exact same order.',
                'Each round the sequence grows longer; longer runs score more.',
                'You have three lives — a wrong tap costs one.',
            ],
        ],
    ];

    public string $screenState = 'content';

    public string $slug = '';

    public string $title = '';

    public string $tagline = '';

    /** @var list<string> */
    public array $steps = [];

    /** @var list<string> */
    public array $skills = [];

    public ?int $bestScore = null;

    /**
     * @var list<array{date: string, score: string, accuracy: string, correct: string, duration: string, avg: string}>
     */
    public array $history = [];

    public string $difficultyLabel = '';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding');

            return;
        }

        $this->loadDetail();
    }

    public function render(): Element
    {
        return $this->view('screens.game-detail');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title($this->title !== '' ? $this->title : 'Game')
            ->back(true);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    /**
     * Return to the games library from the error state.
     */
    public function backToGames(): void
    {
        $this->navigate('/games');
    }

    /**
     * Launch a fresh free-play session and open the game.
     */
    public function play(): void
    {
        if ($this->screenState !== 'content') {
            return;
        }

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                $this->replace('/onboarding');

                return;
            }

            $game = Game::query()->playable()->where('slug', $this->slug)->firstOrFail();
            $level = $this->levelFor($game, $profile->difficulty_preference);
            $session = app(GameSessionService::class)->startFreePlay($profile, $game, $level);

            app(HapticService::class)->trigger(HapticFeedback::Impact);

            $this->navigate('/play/'.$this->slug.'/'.$session->getKey())
                ->transition($this->reducedMotion ? Transition::None : Transition::SlideFromBottom);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function loadDetail(): void
    {
        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                $this->replace('/onboarding');

                return;
            }

            $this->slug = (string) $this->param('slug');
            $guide = self::GUIDES[$this->slug] ?? null;
            $game = Game::query()->playable()->where('slug', $this->slug)->first();

            if ($guide === null || $game === null) {
                $this->screenState = 'error';

                return;
            }

            $theme = app(ThemeManager::class);
            $this->reducedMotion = $theme->prefersReducedMotion();
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);

            $this->title = $game->name;
            $this->tagline = $guide['tagline'];
            $this->steps = $guide['steps'];
            $this->skills = collect($game->skill_keys)
                ->map(fn (string $skill): string => Str::headline($skill))
                ->values()
                ->all();
            $this->difficultyLabel = $profile->difficulty_preference->label();
            $this->bestScore = Statistic::query()
                ->whereBelongsTo($profile)
                ->where('game_id', $game->getKey())
                ->value('best_score');
            $this->loadHistory($profile->getKey(), $game->getKey());
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    /**
     * Load the most recent completed sessions for this game as a results log.
     */
    private function loadHistory(int $profileId, int $gameId): void
    {
        $this->history = GameSession::query()
            ->where('profile_id', $profileId)
            ->where('game_id', $gameId)
            ->completed()
            ->latest('completed_at')
            ->limit(8)
            ->get()
            ->map(function (GameSession $session): array {
                $attempted = $session->correct_count + $session->incorrect_count + $session->missed_count;
                $durationSeconds = $session->completed_at !== null
                    ? (int) $session->started_at->diffInSeconds($session->completed_at)
                    : 0;

                return [
                    'date' => $session->completed_at?->format('M j') ?? '—',
                    'score' => number_format($session->score ?? 0),
                    'accuracy' => $session->accuracy === null ? '—' : round($session->accuracy).'%',
                    'correct' => $session->correct_count.'/'.max(1, $attempted),
                    'duration' => $this->formatDuration($durationSeconds),
                    'avg' => $session->average_response_ms === null
                        ? '—'
                        : $this->formatMs($session->average_response_ms),
                ];
            })
            ->all();
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }

    private function formatMs(int $milliseconds): string
    {
        return $milliseconds < 1000
            ? $milliseconds.'ms'
            : number_format($milliseconds / 1000, 1).'s';
    }

    private function levelFor(Game $game, Difficulty $difficulty): GameLevel
    {
        $resolved = $difficulty === Difficulty::Adaptive ? Difficulty::Intermediate : $difficulty;

        return GameLevel::query()
            ->whereBelongsTo($game)
            ->active()
            ->where('difficulty', $resolved)
            ->firstOrFail();
    }
}
