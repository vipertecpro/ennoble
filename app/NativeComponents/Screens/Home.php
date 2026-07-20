<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\GameType;
use App\Models\Game;
use App\Models\Profile;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Home\GreetingResolver;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Carbon\CarbonInterface;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class Home extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your home screen could not be loaded. Please try again.';

    public string $greeting = '';

    public string $displayName = 'friend';

    public string $todayLabel = '';

    public string $greetingMessage = 'Pick a game and take a focused few minutes.';

    public string $playSectionTitle = 'Start playing';

    /**
     * @var array{slug: string, title: string, subtitle: string}|null
     */
    public ?array $recentGame = null;

    public int $currentStreak = 0;

    public int $gamesPlayed = 0;

    public ?string $achievementTitle = null;

    public ?string $achievementDescription = null;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public float $pressScale = 1.0;

    public float $pressOpacity = 1.0;

    /**
     * Apply the saved theme, enforce onboarding, and assemble the home screen.
     */
    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $transition = $theme->prefersReducedMotion()
                ? Transition::None
                : Transition::Fade;

            $this->replace('/onboarding')->transition($transition);

            return;
        }

        $this->loadHome();
    }

    public function render(): Element
    {
        return $this->view('screens.home');
    }

    /**
     * Refresh previews after returning from another native screen.
     */
    public function onResume(): void
    {
        $this->loadHome();
    }

    /**
     * Supply the Home title and a concise purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Home')
            ->subtitle('Your offline games')
            ->back(false);
    }

    /**
     * Open a game's detail screen from a play card.
     */
    public function openGame(string $slug): void
    {
        if ($this->recentGame === null || $this->recentGame['slug'] !== $slug) {
            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Impact);

        $navigation = $this->navigate('/games/'.$slug);

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Open the Achievements screen from the latest-badge card.
     */
    public function openAchievements(): void
    {
        $navigation = $this->navigate('/achievements');

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Retry the complete screen after a recoverable local failure.
     */
    public function retryHome(): void
    {
        $this->loadHome();
    }

    private function loadHome(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $greetings = app(GreetingResolver::class);

            $this->greeting = $greetings->greeting(now());
            $this->displayName = $greetings->displayName($profile->display_name);
            $this->todayLabel = now()->format('l, M j');
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->pressScale = $this->reducedMotion ? 1.0 : 0.985;
            $this->pressOpacity = $this->reducedMotion ? 1.0 : DesignTokens::OPACITY['pressed'];

            $this->loadRecentGame($profile);
            $this->loadGlance($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function loadRecentGame(Profile $profile): void
    {
        $games = Game::query()
            ->playable()
            ->whereIn('type', [GameType::WordMatch, GameType::QuickMath, GameType::Recall])
            ->orderBy('sort_order')
            ->get();

        if ($games->isEmpty()) {
            $this->recentGame = null;

            return;
        }

        $previews = app(StatisticsService::class)->gamePreviews($profile);

        // The most recently played game leads; before any play, the first game does.
        $recent = $games
            ->sortByDesc(function (Game $game) use ($previews): int {
                $lastPlayed = $previews->get($game->getKey())['last_played_at'] ?? null;

                return $lastPlayed?->getTimestamp() ?? 0;
            })
            ->first();

        $preview = $previews->get($recent->getKey(), []);
        $hasHistory = ($preview['best_score'] ?? null) !== null;

        $this->recentGame = [
            'slug' => $recent->slug,
            'title' => $recent->name,
            'subtitle' => $this->gameSubtitle($preview),
        ];
        $this->playSectionTitle = $hasHistory ? 'Jump back in' : 'Start playing';
        $this->greetingMessage = $hasHistory
            ? 'Welcome back. Keep your streak alive.'
            : 'Pick a game and take a focused few minutes.';
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    private function gameSubtitle(array $preview): string
    {
        $bestScore = $preview['best_score'] ?? null;

        if ($bestScore === null) {
            return 'Tap to play';
        }

        return 'Best '.number_format($bestScore).' · Played '.$this->formatLastPlayed($preview['last_played_at'] ?? null);
    }

    private function loadGlance(Profile $profile): void
    {
        $overview = app(StatisticsService::class)->overview($profile);
        $this->currentStreak = $overview?->current_streak ?? 0;
        $this->gamesPlayed = $overview?->sessions_completed ?? 0;

        $unlock = app(AchievementService::class)->latestUnlock($profile);
        $this->achievementTitle = $unlock?->achievement->name;
        $this->achievementDescription = $unlock?->achievement->description;
    }

    private function formatLastPlayed(?CarbonInterface $lastPlayedAt): string
    {
        if ($lastPlayedAt === null) {
            return 'recently';
        }

        if ($lastPlayedAt->isToday()) {
            return 'today';
        }

        if ($lastPlayedAt->isYesterday()) {
            return 'yesterday';
        }

        return $lastPlayedAt->format('M j');
    }
}
