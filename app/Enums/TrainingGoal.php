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

    /**
     * The skill keys this goal prioritises. Used to surface and rank the games
     * that train the chosen focus. Balanced returns an empty list — every game
     * is equally relevant.
     *
     * @return list<string>
     */
    public function recommendedSkills(): array
    {
        return match ($this) {
            self::Balanced => [],
            self::Focus => [SkillKey::Focus->value],
            self::ThinkingSpeed => [SkillKey::Speed->value],
            self::Language => [SkillKey::Clarity->value, SkillKey::CriticalReading->value],
            self::MentalSharpness => [SkillKey::Structure->value, SkillKey::Precision->value, SkillKey::Adaptability->value],
        };
    }
}
