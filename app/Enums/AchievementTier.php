<?php

namespace App\Enums;

/**
 * The three earnable badge tiers. Players progress from Bronze upward: within a
 * category the higher tiers require higher thresholds, so a Silver or Gold badge
 * can only be reached once its Bronze thresholds are already met.
 */
enum AchievementTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';

    /**
     * Human label for the tier.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Ascending rank (Bronze lowest).
     */
    public function rank(): int
    {
        return match ($this) {
            self::Bronze => 1,
            self::Silver => 2,
            self::Gold => 3,
        };
    }

    /**
     * How many badges of this tier exist per category.
     */
    public function badgesPerCategory(): int
    {
        return match ($this) {
            self::Bronze => 20,
            self::Silver => 10,
            self::Gold => 5,
        };
    }

    /**
     * Semantic theme color token used to tint the tier's medal.
     */
    public function colorToken(): string
    {
        return match ($this) {
            self::Bronze => 'badge-bronze',
            self::Silver => 'badge-silver',
            self::Gold => 'badge-gold',
        };
    }

    /**
     * Tiers in ascending order.
     *
     * @return list<self>
     */
    public static function ascending(): array
    {
        return [self::Bronze, self::Silver, self::Gold];
    }
}
