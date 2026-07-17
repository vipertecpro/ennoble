<?php

namespace App\Enums;

enum Difficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Adaptive = 'adaptive';

    /**
     * Return the user-facing label for this persisted preference.
     */
    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
            self::Adaptive => 'Adaptive',
        };
    }
}
