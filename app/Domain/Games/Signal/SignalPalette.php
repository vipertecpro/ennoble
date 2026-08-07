<?php

namespace App\Domain\Games\Signal;

/**
 * Signal's ink vocabulary — the game's one source of truth for its colors.
 *
 * These are genuine data-driven colors (the stimulus IS the color), not theme
 * roles, so they live here in PHP rather than as theme tokens, and views read
 * them through {@see self::hex()} instead of pasting hex inline. Every hue is
 * chosen to be nameable and mutually distinguishable at display size.
 */
final class SignalPalette
{
    /**
     * @var array<string, array{label: string, hex: string}>
     */
    private const COLORS = [
        'red' => ['label' => 'Red', 'hex' => '#EF4444'],
        'blue' => ['label' => 'Blue', 'hex' => '#3B82F6'],
        'green' => ['label' => 'Green', 'hex' => '#22C55E'],
        'purple' => ['label' => 'Purple', 'hex' => '#A855F7'],
        'pink' => ['label' => 'Pink', 'hex' => '#EC4899'],
        'teal' => ['label' => 'Teal', 'hex' => '#14B8A6'],
    ];

    /**
     * Every color key, in the order difficulty levels widen through them.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::COLORS);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::COLORS);
    }

    /**
     * The display name a player taps ("Red").
     */
    public static function label(string $key): string
    {
        return self::COLORS[$key]['label'] ?? ucfirst($key);
    }

    /**
     * The ink a stimulus is painted in.
     */
    public static function hex(string $key): string
    {
        return self::COLORS[$key]['hex'] ?? '#94A3B8';
    }

    /**
     * Narrow the vocabulary to a validated pool, falling back to the three
     * unmistakable primaries when a level configures nothing usable.
     *
     * @param  array<int, mixed>  $configured
     * @return list<string>
     */
    public static function pool(array $configured): array
    {
        $pool = array_values(array_unique(array_filter(
            $configured,
            static fn (mixed $key): bool => is_string($key) && self::has($key),
        )));

        return count($pool) >= 2 ? $pool : ['red', 'blue', 'green'];
    }
}
