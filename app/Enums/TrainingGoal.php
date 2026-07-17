<?php

namespace App\Enums;

enum TrainingGoal: string
{
    case Balanced = 'balanced';
    case Focus = 'focus';
    case ThinkingSpeed = 'thinking_speed';
    case Language = 'language';
    case MentalSharpness = 'mental_sharpness';

    /**
     * Return the user-facing onboarding label for this persisted goal.
     */
    public function label(): string
    {
        return match ($this) {
            self::Balanced => 'General Improvement',
            self::Focus => 'Improve Focus',
            self::ThinkingSpeed => 'Improve Thinking Speed',
            self::Language => 'Improve Communication',
            self::MentalSharpness => 'Stay Mentally Sharp',
        };
    }
}
