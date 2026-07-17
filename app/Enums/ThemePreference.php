<?php

namespace App\Enums;

enum ThemePreference: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    /**
     * Return the user-facing label for this appearance preference.
     */
    public function label(): string
    {
        return match ($this) {
            self::System => 'Use Device Setting',
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }
}
