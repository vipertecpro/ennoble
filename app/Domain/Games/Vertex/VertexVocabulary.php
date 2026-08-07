<?php

namespace App\Domain\Games\Vertex;

/**
 * Barrage's invader vocabulary — the two independent attributes an invader
 * carries, and the criteria a wave can be built from.
 *
 * Shape and colour are deliberately ORTHOGONAL. That is what makes a wave a
 * visual-search task rather than a lookup: with both varying, "fire at rings"
 * forces the player to ignore colour, and "fire at blue rings" forces them to
 * hold both attributes at once — the conjunction that turns an easy pop-out
 * search into a slow, effortful one.
 *
 * These hexes are genuine data-driven colour (the invader IS its colour), so
 * they live here in PHP rather than as theme tokens, and views read them
 * through {@see self::colourHex()} instead of pasting hex inline.
 */
final class VertexVocabulary
{
    /** @var array<string, string> */
    private const SHAPES = [
        'disc' => 'Disc',
        'block' => 'Block',
        'bar' => 'Bar',
        'ring' => 'Ring',
    ];

    /** @var array<string, array{label: string, hex: string}> */
    private const COLOURS = [
        'blue' => ['label' => 'Blue', 'hex' => '#38BDF8'],
        'amber' => ['label' => 'Amber', 'hex' => '#F59E0B'],
        'violet' => ['label' => 'Violet', 'hex' => '#A855F7'],
        'rose' => ['label' => 'Rose', 'hex' => '#F43F5E'],
    ];

    /** @return list<string> */
    public static function shapes(): array
    {
        return array_keys(self::SHAPES);
    }

    /** @return list<string> */
    public static function colours(): array
    {
        return array_keys(self::COLOURS);
    }

    public static function hasShape(string $key): bool
    {
        return array_key_exists($key, self::SHAPES);
    }

    public static function hasColour(string $key): bool
    {
        return array_key_exists($key, self::COLOURS);
    }

    public static function shapeLabel(string $key): string
    {
        return self::SHAPES[$key] ?? ucfirst($key);
    }

    public static function colourLabel(string $key): string
    {
        return self::COLOURS[$key]['label'] ?? ucfirst($key);
    }

    public static function colourHex(string $key): string
    {
        return self::COLOURS[$key]['hex'] ?? '#94A3B8';
    }

    /**
     * Narrow a configured pool to valid entries, falling back to a playable
     * default. Two is the floor for either attribute — a single-valued pool
     * would make every invader identical on that axis and the criterion moot.
     *
     * @param  array<int, mixed>  $configured
     * @param  list<string>  $fallback
     * @return list<string>
     */
    public static function pool(array $configured, callable $isValid, array $fallback): array
    {
        $pool = array_values(array_unique(array_filter(
            $configured,
            static fn (mixed $key): bool => is_string($key) && $isValid($key),
        )));

        return count($pool) >= 2 ? $pool : $fallback;
    }

    /**
     * Does this invader satisfy the wave's firing criterion?
     *
     * @param  array{type: string, shape?: string, colour?: string}  $criterion
     * @param  array{shape: string, colour: string}  $invader
     */
    public static function matches(array $criterion, array $invader): bool
    {
        return match ($criterion['type']) {
            'shape' => $invader['shape'] === ($criterion['shape'] ?? null),
            'colour' => $invader['colour'] === ($criterion['colour'] ?? null),
            'both' => $invader['shape'] === ($criterion['shape'] ?? null)
                && $invader['colour'] === ($criterion['colour'] ?? null),
            // Negation is the hardest single-attribute form: the player has to
            // suppress the obvious "find the named thing" response.
            'not_shape' => $invader['shape'] !== ($criterion['shape'] ?? null),
            'not_colour' => $invader['colour'] !== ($criterion['colour'] ?? null),
            default => false,
        };
    }

    /**
     * The standing order shown above the wave.
     *
     * @param  array{type: string, shape?: string, colour?: string}  $criterion
     */
    public static function orderLabel(array $criterion): string
    {
        return match ($criterion['type']) {
            'shape' => 'Fire at '.self::shapeLabel($criterion['shape'] ?? '').'s',
            'colour' => 'Fire at '.self::colourLabel($criterion['colour'] ?? ''),
            'both' => 'Fire at '.self::colourLabel($criterion['colour'] ?? '')
                .' '.self::shapeLabel($criterion['shape'] ?? '').'s',
            'not_shape' => 'Fire at everything but '.self::shapeLabel($criterion['shape'] ?? '').'s',
            'not_colour' => 'Fire at everything but '.self::colourLabel($criterion['colour'] ?? ''),
            default => 'Hold fire',
        };
    }
}
