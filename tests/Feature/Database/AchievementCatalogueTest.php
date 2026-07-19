<?php

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Support\Collection;

test('the seeded catalogue holds exactly 175 badges', function () {
    expect(Achievement::query()->count())->toBe(175);
});

test('every category holds 20 bronze, 10 silver and 5 gold badges', function () {
    $byTypeAndTier = Achievement::query()
        ->get()
        ->groupBy([
            fn (Achievement $achievement): string => $achievement->type->value,
            fn (Achievement $achievement): string => $achievement->tier->value,
        ]);

    foreach (AchievementType::cases() as $type) {
        $tiers = $byTypeAndTier->get($type->value);

        expect($tiers)->not->toBeNull()
            ->and($tiers->get(AchievementTier::Bronze->value))->toHaveCount(20)
            ->and($tiers->get(AchievementTier::Silver->value))->toHaveCount(10)
            ->and($tiers->get(AchievementTier::Gold->value))->toHaveCount(5);
    }
});

test('every seeded badge is overall-scope and active with a threshold criterion', function () {
    Achievement::query()->each(function (Achievement $achievement): void {
        expect($achievement->game_id)->toBeNull()
            ->and($achievement->is_active)->toBeTrue()
            ->and(data_get($achievement->criterion, 'threshold'))->toBeInt();
    });
});

test('thresholds within each category move monotonically by sort order', function () {
    Achievement::query()
        ->get()
        ->groupBy(fn (Achievement $achievement): string => $achievement->type->value)
        ->each(function (Collection $badges, string $typeValue): void {
            $type = AchievementType::from($typeValue);
            $thresholds = $badges
                ->sortBy('sort_order')
                ->map(fn (Achievement $achievement): int => (int) data_get($achievement->criterion, 'threshold'))
                ->values()
                ->all();

            for ($i = 1, $count = count($thresholds); $i < $count; $i++) {
                if ($type === AchievementType::Speed) {
                    // Speed rewards lower response times, so raw values descend.
                    expect($thresholds[$i])->toBeLessThan($thresholds[$i - 1]);
                } else {
                    expect($thresholds[$i])->toBeGreaterThan($thresholds[$i - 1]);
                }
            }
        });
});
