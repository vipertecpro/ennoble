<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\AchievementType;
use App\Models\Profile as LocalProfile;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

#[Lazy]
final class Achievements extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your achievements could not be loaded. Please try again.';

    public int $totalEarned = 0;

    public int $totalBadges = 0;

    public float $totalProgress = 0.0;

    /**
     * @var list<array{label: string, color: string, earned: int, total: int}>
     */
    public array $tierSummary = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $categories = [];

    public bool $hasEvidence = false;

    public string $streakLabel = '0';

    public string $accuracyLabel = 'Not measured';

    public string $speedLabel = 'Not measured';

    public string $gamesLabel = '0';

    public string $bestLabel = 'None yet';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public float $pressScale = 1.0;

    public float $pressOpacity = 1.0;

    /**
     * Apply the saved theme, enforce onboarding, and assemble the badge board.
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

        $this->loadScreen();
    }

    public function render(): Element
    {
        return $this->view('screens.achievements');
    }

    /**
     * Refresh badge evidence after returning from another native screen.
     */
    public function onResume(): void
    {
        $this->loadScreen();
    }

    /**
     * Supply the Achievements title and purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Achievements')
            ->subtitle('Bronze, Silver, and Gold badges you have earned')
            ->back(false);
    }

    /**
     * Open a category's badge wall.
     */
    public function openCategory(string $category): void
    {
        if (AchievementType::tryFrom($category) === null) {
            return;
        }

        $navigation = $this->navigate('/achievements/'.$category);

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Retry the complete screen after a recoverable local failure.
     */
    public function retryAchievements(): void
    {
        $this->loadScreen();
    }

    private function loadScreen(): void
    {
        $this->screenState = 'content';

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
            $this->pressOpacity = $this->reducedMotion ? 1.0 : DesignTokens::OPACITY['pressed'];

            $this->loadBadges($profile);
            $this->loadStats($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function loadBadges(LocalProfile $profile): void
    {
        $board = app(AchievementService::class)->board($profile);

        $this->totalEarned = $board['earned'];
        $this->totalBadges = $board['total'];
        $this->totalProgress = $board['total'] > 0
            ? round($board['earned'] / $board['total'], 3)
            : 0.0;
        $this->tierSummary = array_values($board['tiers']);
        $this->categories = $board['categories'];
    }

    private function loadStats(LocalProfile $profile): void
    {
        $overview = app(StatisticsService::class)->overview($profile);

        $this->hasEvidence = $overview !== null && $overview->sessions_completed > 0;
        $this->streakLabel = (string) ($overview?->current_streak ?? 0);
        $this->accuracyLabel = $overview?->accuracy === null
            ? 'Not measured'
            : round($overview->accuracy).'%';
        $this->speedLabel = $this->formatResponseTime($overview?->average_response_ms);
        $this->gamesLabel = (string) ($overview?->sessions_completed ?? 0);
        $this->bestLabel = ($overview?->best_score ?? 0) > 0
            ? number_format($overview->best_score)
            : 'None yet';
    }

    private function formatResponseTime(?int $averageResponseMs): string
    {
        if ($averageResponseMs === null) {
            return 'Not measured';
        }

        if ($averageResponseMs < 1000) {
            return $averageResponseMs.' ms';
        }

        return number_format($averageResponseMs / 1000, 1).' s';
    }
}
