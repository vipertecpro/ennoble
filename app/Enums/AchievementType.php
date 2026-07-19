<?php

namespace App\Enums;

/**
 * A badge category. Each type is one activity dimension measured from the
 * profile's overall statistics; every category holds Bronze/Silver/Gold badges
 * unlocked by clearing an ascending ladder of thresholds.
 */
enum AchievementType: string
{
    case Streak = 'streak';
    case Accuracy = 'accuracy';
    case Speed = 'speed';
    case Dedication = 'dedication';
    case Mastery = 'mastery';

    /**
     * The overall-scope Statistic column this category is measured from.
     */
    public function metric(): string
    {
        return match ($this) {
            self::Streak => 'current_streak',
            self::Accuracy => 'accuracy',
            self::Speed => 'average_response_ms',
            self::Dedication => 'sessions_completed',
            self::Mastery => 'best_score',
        };
    }

    /**
     * How a measured value is compared to a badge threshold. Speed rewards lower
     * response times, so it clears when the value is at or below the threshold.
     */
    public function comparator(): string
    {
        return $this === self::Speed ? '<=' : '>=';
    }

    /**
     * Display label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Streak => 'Streaks',
            self::Accuracy => 'Accuracy',
            self::Speed => 'Speed',
            self::Dedication => 'Dedication',
            self::Mastery => 'Mastery',
        };
    }

    /**
     * One-line description of what the category rewards.
     */
    public function tagline(): string
    {
        return match ($this) {
            self::Streak => 'Play on consecutive days.',
            self::Accuracy => 'Answer correctly across games.',
            self::Speed => 'Answer faster on average.',
            self::Dedication => 'Complete more games overall.',
            self::Mastery => 'Reach higher single-game scores.',
        };
    }

    /**
     * Format a raw threshold or measured value for display.
     */
    public function formatValue(int|float|null $value): string
    {
        if ($value === null) {
            return match ($this) {
                self::Speed => 'Not measured',
                default => '0',
            };
        }

        return match ($this) {
            self::Streak => ((int) $value).' '.((int) $value === 1 ? 'day' : 'days'),
            self::Accuracy => ((int) round((float) $value)).'%',
            self::Speed => ((int) $value).' ms',
            self::Dedication => ((int) $value).' '.((int) $value === 1 ? 'game' : 'games'),
            self::Mastery => number_format((int) $value).' pts',
        };
    }
}
