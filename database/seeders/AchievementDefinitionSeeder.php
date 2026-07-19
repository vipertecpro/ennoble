<?php

namespace Database\Seeders;

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the full badge catalogue: five activity categories, each with an
 * ascending ladder of 20 Bronze, 10 Silver and 5 Gold badges (175 total).
 *
 * Thresholds within a category increase monotonically in difficulty, so a
 * player always clears Bronze before Silver and Silver before Gold. Every
 * badge lives in the "overall" statistics scope. Idempotent via slug upsert —
 * safe on fresh installs and on upgrades of existing on-device databases.
 */
class AchievementDefinitionSeeder extends Seeder
{
    /**
     * Ordered difficulty ladders per category. The first 20 entries are Bronze,
     * the next 10 Silver, the final 5 Gold. Speed descends (lower is harder);
     * every other category ascends.
     *
     * @var array<string, list<int>>
     */
    private const LADDERS = [
        'streak' => [
            2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21,
            24, 28, 32, 36, 40, 45, 50, 55, 60, 70,
            90, 120, 150, 200, 365,
        ],
        'accuracy' => [
            40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70, 71, 72, 73, 74,
            75, 77, 79, 81, 83, 85, 87, 89, 91, 93,
            95, 96, 97, 98, 99,
        ],
        'speed' => [
            5000, 4800, 4600, 4400, 4200, 4000, 3800, 3600, 3400, 3200,
            3000, 2900, 2800, 2700, 2600, 2500, 2400, 2300, 2200, 2100,
            2000, 1900, 1800, 1700, 1600, 1500, 1400, 1300, 1200, 1100,
            1000, 900, 800, 700, 600,
        ],
        'dedication' => [
            1, 2, 3, 4, 5, 6, 7, 8, 10, 12, 14, 16, 18, 20, 25, 30, 35, 40, 45, 50,
            60, 70, 80, 90, 100, 120, 140, 160, 180, 200,
            250, 300, 400, 500, 750,
        ],
        'mastery' => [
            100, 200, 300, 400, 500, 600, 700, 800, 900, 1000,
            1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000,
            2200, 2400, 2600, 2800, 3000, 3300, 3600, 4000, 4500, 5000,
            6000, 7000, 8000, 10000, 12000,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $rows = [];
        $sortOrder = 0;

        foreach (AchievementType::cases() as $type) {
            $ladder = self::LADDERS[$type->value];
            $tierIndex = 0;

            foreach ($ladder as $position => $threshold) {
                $tier = $this->tierForPosition($position);
                $tierIndex = $this->tierPosition($position) + 1;
                $sortOrder++;

                $rows[] = [
                    'game_id' => null,
                    'slug' => $type->value.'-'.$tier->value.'-'.$tierIndex,
                    'name' => $type->label().' — '.$type->formatValue($threshold),
                    'description' => $this->describe($type, $threshold),
                    'type' => $type->value,
                    'tier' => $tier->value,
                    'criterion' => json_encode(['threshold' => $threshold], JSON_THROW_ON_ERROR),
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('achievements')->upsert(
            $rows,
            ['slug'],
            ['game_id', 'name', 'description', 'type', 'tier', 'criterion', 'sort_order', 'is_active', 'updated_at'],
        );
    }

    /**
     * Map a 0-based ladder position to its tier (first 20 Bronze, next 10 Silver, last 5 Gold).
     */
    private function tierForPosition(int $position): AchievementTier
    {
        return match (true) {
            $position < 20 => AchievementTier::Bronze,
            $position < 30 => AchievementTier::Silver,
            default => AchievementTier::Gold,
        };
    }

    /**
     * The 0-based index of a ladder position within its own tier.
     */
    private function tierPosition(int $position): int
    {
        return match (true) {
            $position < 20 => $position,
            $position < 30 => $position - 20,
            default => $position - 30,
        };
    }

    private function describe(AchievementType $type, int $threshold): string
    {
        return match ($type) {
            AchievementType::Streak => 'Play on '.$threshold.' consecutive days.',
            AchievementType::Accuracy => 'Reach '.$threshold.'% overall accuracy.',
            AchievementType::Speed => 'Average '.$threshold.' ms or faster across your answers.',
            AchievementType::Dedication => 'Complete '.$threshold.' '.($threshold === 1 ? 'game' : 'games').'.',
            AchievementType::Mastery => 'Score '.number_format($threshold).' points in a single game.',
        };
    }
}
