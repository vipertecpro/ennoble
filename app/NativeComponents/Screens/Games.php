<?php

namespace App\NativeComponents\Screens;

use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Profile;
use App\NativeUI\Dialogs\InteractsWithDialogs;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class Games extends NativeComponent
{
    use InteractsWithDialogs;

    /**
     * @var array<string, string>
     */
    private const CATEGORIES = [
        'all' => 'All',
        'focus' => 'Focus',
        'language' => 'Language',
        'logic' => 'Logic',
        'memory' => 'Memory',
        'speed' => 'Speed',
    ];

    public string $libraryState = 'content';

    public string $libraryError = 'Your games library could not be loaded. Please try again.';

    public bool $statisticsLoading = true;

    public ?string $statisticsError = null;

    public string $searchQuery = '';

    public string $selectedCategory = 'all';

    /**
     * @var list<array{key: string, label: string}>
     */
    public array $categories = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $playableGames = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $filteredPlayableGames = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $featuredGame = null;

    public bool $featuredVisible = false;

    public string $emptyTitle = 'No games found';

    public string $emptyDescription = 'Try another category to discover more training experiences.';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public float $pressScale = 1.0;

    public float $pressOpacity = 1.0;

    /**
     * Apply the saved theme and assemble the entirely local games catalog.
     */
    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding')->transition(
                $theme->prefersReducedMotion() ? Transition::None : Transition::Fade,
            );

            return;
        }

        $this->categories = collect(self::CATEGORIES)
            ->map(fn (string $label, string $key): array => compact('key', 'label'))
            ->values()
            ->all();

        $this->loadLibrary();
    }

    public function render(): Element
    {
        return $this->view('screens.games');
    }

    /**
     * Refresh evidence-backed previews when returning to the Games tab.
     */
    public function onResume(): void
    {
        $this->loadLibrary();
    }

    /**
     * Supply a concise purpose to the native top bar.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Games')
            ->subtitle('Curated offline training')
            ->back(false);
    }

    /**
     * Reapply local filtering when the debounced search field changes.
     */
    public function updatedSearchQuery(): void
    {
        $this->applyFilters();
    }

    /**
     * Select one of the supported library categories.
     */
    public function setCategory(string $category): void
    {
        if (! array_key_exists($category, self::CATEGORIES)) {
            return;
        }

        $this->selectedCategory = $category;
        $this->applyFilters();

        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Reset filtering from the no-match empty state.
     */
    public function showAllGames(): void
    {
        $this->setCategory('all');
    }

    /**
     * Open the instructions/detail screen for a playable game entry.
     */
    public function openGame(string $slug): void
    {
        if (! collect($this->playableGames)->contains('slug', $slug)) {
            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Impact);

        $this->navigate('/games/'.$slug)
            ->transition($this->reducedMotion ? Transition::None : Transition::ParallaxPush);
    }

    /**
     * Retry the complete local catalog after a recoverable failure.
     */
    public function retryLibrary(): void
    {
        $this->loadLibrary();

        if ($this->libraryState === 'error') {
            app(ToastService::class)->show(
                'The games library is still unavailable.',
                ToastType::Error,
            );
        }
    }

    /**
     * Retry evidence-backed statistics without changing filters.
     */
    public function retryStatistics(): void
    {
        $this->loadLibrary();

        if ($this->statisticsError !== null) {
            app(ToastService::class)->show(
                'Game statistics are still unavailable.',
                ToastType::Error,
            );
        }
    }

    private function loadLibrary(): void
    {
        $this->libraryState = 'content';
        $this->libraryError = 'Your games library could not be loaded. Please try again.';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->pressScale = $this->reducedMotion ? 1.0 : 0.985;
            $this->pressOpacity = $this->reducedMotion
                ? 1.0
                : DesignTokens::OPACITY['pressed'];

            $games = Game::query()
                ->playable()
                ->whereIn('type', [GameType::WordMatch, GameType::QuickMath])
                ->orderBy('sort_order')
                ->get();

            if ($games->isEmpty()) {
                throw new DomainException('At least one playable game definition is required for the Games library.');
            }

            $this->loadPlayableGames($profile, $games);
            $this->applyFilters();
        } catch (Throwable $exception) {
            report($exception);

            $this->libraryState = 'error';
            $this->playableGames = [];
            $this->filteredPlayableGames = [];
            $this->featuredGame = null;
            $this->featuredVisible = false;
        }
    }

    /**
     * @param  Collection<int, Game>  $games
     */
    private function loadPlayableGames(Profile $profile, Collection $games): void
    {
        $this->statisticsLoading = true;
        $this->statisticsError = null;
        $previews = collect();

        try {
            $previews = app(StatisticsService::class)->gamePreviews($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->statisticsError = 'Your game statistics could not be loaded. Your training history is safe.';
        } finally {
            $this->statisticsLoading = false;
        }

        $this->playableGames = $games
            ->map(function (Game $game) use ($profile, $previews): array {
                $level = $this->levelForProfile($game, $profile);
                $preview = $previews->get($game->getKey(), []);
                $sessionCount = (int) ($preview['session_count'] ?? 0);
                $categories = $this->categoriesFor($game->type);

                return [
                    'slug' => $game->slug,
                    'title' => $game->name,
                    'description' => $game->description,
                    'category' => self::CATEGORIES[$categories[0]],
                    'categories' => $categories,
                    'duration' => 'About '.$this->estimatedGameDurationMinutes($level).' min',
                    'difficulty' => $profile->difficulty_preference->label(),
                    'level' => $level->name,
                    'skills' => collect($game->skill_keys)
                        ->map(fn (string $skill): string => Str::headline($skill))
                        ->values()
                        ->all(),
                    'best_score' => $preview['best_score'] ?? null,
                    'session_count' => $sessionCount,
                    'completion_count' => (int) ($preview['completion_count'] ?? 0),
                    'completion_rate' => $preview['completion_rate'] ?? null,
                    'last_played' => $this->formatLastPlayed($preview['last_played_at'] ?? null),
                    'has_history' => $sessionCount > 0,
                    'hero_action' => $sessionCount > 0 ? 'Play Again' : 'Start Training',
                ];
            })
            ->values()
            ->all();
        $this->featuredGame = collect($this->playableGames)
            ->firstWhere('slug', 'word-match');
    }

    private function applyFilters(): void
    {
        $this->filteredPlayableGames = collect($this->playableGames)
            ->filter(fn (array $game): bool => $this->matchesFilters($game))
            ->values()
            ->all();
        $this->featuredVisible = $this->featuredGame !== null
            && $this->matchesFilters($this->featuredGame);

        if ($this->hasFilteredResults()) {
            return;
        }

        if (Str::of($this->searchQuery)->squish()->isNotEmpty()) {
            $this->emptyTitle = 'No search results';
            $this->emptyDescription = 'Try another title, category, or training focus.';

            return;
        }

        $this->emptyTitle = 'No games found';
        $this->emptyDescription = 'Try All or another category to discover more training experiences.';
    }

    /**
     * @param  array<string, mixed>  $game
     */
    private function matchesFilters(array $game): bool
    {
        $categories = $game['categories'] ?? [];

        if ($this->selectedCategory !== 'all'
            && ! in_array($this->selectedCategory, $categories, true)) {
            return false;
        }

        $query = Str::of($this->searchQuery)->squish()->lower()->toString();

        if ($query === '') {
            return true;
        }

        $haystack = Str::of(implode(' ', [
            (string) ($game['title'] ?? ''),
            (string) ($game['category'] ?? ''),
            implode(' ', array_map(
                fn (string $category): string => self::CATEGORIES[$category] ?? $category,
                $categories,
            )),
            (string) ($game['description'] ?? ''),
        ]))->lower();

        return $haystack->contains($query);
    }

    private function hasFilteredResults(): bool
    {
        return $this->filteredPlayableGames !== [];
    }

    /**
     * @return list<string>
     */
    private function categoriesFor(GameType $gameType): array
    {
        return match ($gameType) {
            GameType::WordMatch => ['language', 'focus'],
            GameType::QuickMath => ['logic', 'speed'],
        };
    }

    /**
     * Resolve the active level matching a profile's saved difficulty.
     */
    private function levelForProfile(Game $game, Profile $profile): GameLevel
    {
        $levelDifficulty = $profile->difficulty_preference === Difficulty::Adaptive
            ? Difficulty::Intermediate
            : $profile->difficulty_preference;

        return GameLevel::query()
            ->whereBelongsTo($game)
            ->active()
            ->where('difficulty', $levelDifficulty)
            ->firstOrFail();
    }

    /**
     * Estimate an individual game's duration from its configured round count.
     */
    private function estimatedGameDurationMinutes(GameLevel $gameLevel): int
    {
        return max(2, min(5, (int) ceil($gameLevel->round_count / 2)));
    }

    private function formatLastPlayed(?CarbonInterface $lastPlayedAt): string
    {
        if ($lastPlayedAt === null) {
            return 'Not played yet';
        }

        if ($lastPlayedAt->isToday()) {
            return 'Today';
        }

        if ($lastPlayedAt->isYesterday()) {
            return 'Yesterday';
        }

        return $lastPlayedAt->format('M j');
    }
}
