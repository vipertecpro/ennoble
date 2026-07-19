<?php

namespace App\Domain\Achievements;

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AchievementService
{
    /**
     * Evaluate active badge definitions against the profile's overall statistics
     * and persist any newly cleared unlocks idempotently.
     *
     * Thresholds within a category ascend, so clearing a high tier naturally
     * unlocks every lower badge in the same pass — Bronze is always earned
     * before Silver, and Silver before Gold.
     *
     * @return Collection<int, AchievementUnlock>
     */
    public function evaluate(Profile $profile, ?GameSession $gameSession = null): Collection
    {
        if ($gameSession !== null && $gameSession->profile_id !== $profile->getKey()) {
            throw new LogicException('Achievement session evidence must belong to the same profile.');
        }

        return DB::transaction(function () use ($profile, $gameSession): Collection {
            $statistic = Statistic::query()
                ->whereBelongsTo($profile)
                ->overall()
                ->first();
            $unlockedAchievementIds = AchievementUnlock::query()
                ->whereBelongsTo($profile)
                ->pluck('achievement_id');
            $newUnlocks = collect();

            Achievement::query()
                ->active()
                ->whereNotIn('id', $unlockedAchievementIds)
                ->orderBy('sort_order')
                ->get()
                ->each(function (Achievement $achievement) use ($profile, $gameSession, $statistic, $newUnlocks): void {
                    $evidence = $this->matchingEvidence($achievement, $statistic);

                    if ($evidence === null) {
                        return;
                    }

                    $unlock = AchievementUnlock::query()->firstOrCreate(
                        [
                            'profile_id' => $profile->getKey(),
                            'achievement_id' => $achievement->getKey(),
                        ],
                        [
                            'game_session_id' => $gameSession?->getKey(),
                            'unlocked_at' => now(),
                            'evidence' => $evidence,
                        ],
                    );

                    if ($unlock->wasRecentlyCreated) {
                        $newUnlocks->push($unlock);
                    }
                });

            return $newUnlocks;
        });
    }

    /**
     * Return every active badge with this profile's unlock evidence attached.
     *
     * @return Collection<int, Achievement>
     */
    public function overview(Profile $profile): Collection
    {
        return Achievement::query()
            ->active()
            ->with(['unlocks' => fn ($query) => $query->whereBelongsTo($profile)])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Build the Achievements screen board: an overall tier summary plus a
     * per-category breakdown of earned badges and the next badge to chase.
     *
     * @return array{
     *     earned: int,
     *     total: int,
     *     tiers: array<string, array{label: string, color: string, earned: int, total: int}>,
     *     categories: list<array<string, mixed>>
     * }
     */
    public function board(Profile $profile): array
    {
        $achievements = $this->overview($profile);
        $statistic = Statistic::query()->whereBelongsTo($profile)->overall()->first();

        $tiers = [];

        foreach (AchievementTier::ascending() as $tier) {
            $inTier = $achievements->where('tier', $tier);
            $tiers[$tier->value] = [
                'label' => $tier->label(),
                'color' => $tier->colorToken(),
                'earned' => $inTier->filter($this->isUnlocked(...))->count(),
                'total' => $inTier->count(),
            ];
        }

        $grouped = $achievements->groupBy(fn (Achievement $achievement): string => $achievement->type->value);
        $categories = collect(AchievementType::cases())
            ->map(function (AchievementType $type) use ($grouped, $statistic): array {
                $items = ($grouped->get($type->value) ?? collect())->sortBy('sort_order')->values();
                $value = $statistic?->getAttribute($type->metric());
                $next = $items->first(fn (Achievement $achievement): bool => ! $this->isUnlocked($achievement));

                $tierBuckets = [];

                foreach (AchievementTier::ascending() as $tier) {
                    $inTier = $items->where('tier', $tier);
                    $tierBuckets[] = [
                        'label' => $tier->label(),
                        'color' => $tier->colorToken(),
                        'earned' => $inTier->filter($this->isUnlocked(...))->count(),
                        'total' => $tier->badgesPerCategory(),
                    ];
                }

                return [
                    'key' => $type->value,
                    'label' => $type->label(),
                    'tagline' => $type->tagline(),
                    'currentLabel' => $type->formatValue($value),
                    'earned' => $items->filter($this->isUnlocked(...))->count(),
                    'total' => $items->count(),
                    'tiers' => $tierBuckets,
                    'nextLabel' => $next !== null
                        ? $type->formatValue((int) data_get($next->criterion, 'threshold'))
                        : null,
                    'nextTier' => $next?->tier->label(),
                ];
            })
            ->all();

        return [
            'earned' => $achievements->filter($this->isUnlocked(...))->count(),
            'total' => $achievements->count(),
            'tiers' => $tiers,
            'categories' => $categories,
        ];
    }

    /**
     * Return every badge in one category with this profile's unlock state,
     * ordered by ascending difficulty.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function categoryBadges(Profile $profile, AchievementType $category): Collection
    {
        return $this->overview($profile)
            ->where('type', $category)
            ->sortBy('sort_order')
            ->map(fn (Achievement $achievement): array => [
                'name' => $achievement->name,
                'description' => $achievement->description,
                'tier' => $achievement->tier,
                'thresholdLabel' => $category->formatValue((int) data_get($achievement->criterion, 'threshold')),
                'unlocked' => $this->isUnlocked($achievement),
                'unlockedAt' => $achievement->unlocks->first()?->unlocked_at,
            ])
            ->values();
    }

    /**
     * Return the latest unlocked badge for a lightweight preview.
     */
    public function latestUnlock(Profile $profile): ?AchievementUnlock
    {
        return AchievementUnlock::query()
            ->whereBelongsTo($profile)
            ->with('achievement')
            ->latest('unlocked_at')
            ->latest('id')
            ->first();
    }

    /**
     * Whether the profile has unlocked the badge (its eager-loaded unlocks are
     * already scoped to the profile by overview()).
     */
    private function isUnlocked(Achievement $achievement): bool
    {
        return $achievement->unlocks->isNotEmpty();
    }

    /**
     * Compare the measured metric for a badge's category against its threshold.
     *
     * @return array{metric: string, value: int|float, threshold: int}|null
     */
    private function matchingEvidence(Achievement $achievement, ?Statistic $statistic): ?array
    {
        $type = $achievement->type;
        $metric = $type->metric();
        $threshold = (int) data_get($achievement->criterion, 'threshold', 0);
        $value = $statistic?->getAttribute($metric);

        if ($value === null) {
            return null;
        }

        $cleared = $type->comparator() === '<='
            ? $value <= $threshold
            : $value >= $threshold;

        if (! $cleared) {
            return null;
        }

        return [
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
        ];
    }
}
