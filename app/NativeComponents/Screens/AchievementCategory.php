<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Models\Profile as LocalProfile;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Collection;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

/**
 * The badge wall for one activity category — its 20 Bronze, 10 Silver and
 * 5 Gold badges grouped by tier, each shown earned or still to unlock.
 */
final class AchievementCategory extends NativeComponent
{
    public string $screenState = 'content';

    public string $categoryLabel = 'Badges';

    public string $categoryTagline = '';

    public string $currentLabel = '';

    public int $earnedCount = 0;

    public int $totalCount = 0;

    /**
     * @var list<array<string, mixed>>
     */
    public array $tierGroups = [];

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding');

            return;
        }

        $this->loadCategory();
    }

    public function render(): Element
    {
        return $this->view('screens.achievement-category');
    }

    public function onResume(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->loadCategory();
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title($this->categoryLabel !== '' ? $this->categoryLabel : 'Badges')
            ->subtitle('Bronze → Silver → Gold')
            ->back(true);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function goBack(): void
    {
        $this->back();
    }

    public function backToAchievements(): void
    {
        $this->navigate('/achievements');
    }

    private function loadCategory(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $type = AchievementType::tryFrom((string) $this->param('category'));

            if ($type === null) {
                $this->screenState = 'error';

                return;
            }

            $theme = app(ThemeManager::class);
            $this->reducedMotion = $theme->prefersReducedMotion();
            $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);

            $this->categoryLabel = $type->label();
            $this->categoryTagline = $type->tagline();

            $overview = app(StatisticsService::class)->overview($profile);
            $this->currentLabel = $type->formatValue($overview?->getAttribute($type->metric()));

            $this->mapBadges($profile, $type);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function mapBadges(LocalProfile $profile, AchievementType $type): void
    {
        $badges = app(AchievementService::class)->categoryBadges($profile, $type);

        $this->earnedCount = $badges->filter(fn (array $badge): bool => $badge['unlocked'])->count();
        $this->totalCount = $badges->count();

        $this->tierGroups = collect(AchievementTier::ascending())
            ->map(function (AchievementTier $tier) use ($badges): array {
                $inTier = $badges->filter(
                    fn (array $badge): bool => $badge['tier'] === $tier,
                )->values();

                return [
                    'label' => $tier->label(),
                    'color' => $tier->colorToken(),
                    'earned' => $inTier->filter(fn (array $badge): bool => $badge['unlocked'])->count(),
                    'total' => $tier->badgesPerCategory(),
                    'badges' => $this->primitiveBadges($inTier),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $badges
     * @return list<array{name: string, thresholdLabel: string, unlocked: bool, unlockedLabel: ?string}>
     */
    private function primitiveBadges(Collection $badges): array
    {
        return $badges
            ->map(fn (array $badge): array => [
                'name' => $badge['name'],
                'thresholdLabel' => $badge['thresholdLabel'],
                'unlocked' => $badge['unlocked'],
                'unlockedLabel' => $badge['unlockedAt']?->format('M j, Y'),
            ])
            ->all();
    }
}
