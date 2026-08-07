<?php

namespace App\Domain\Games\Vertex;

/**
 * Vertex's object vocabulary — the game's one source of truth for the forms
 * that fly down the tunnel.
 *
 * Each form is identified by BOTH a silhouette and a colour so it can be read
 * at speed and at any depth; neither channel alone carries the answer. The hex
 * values are genuine data-driven colour (the object IS its colour), so they
 * live here rather than as theme tokens, and every one is kept clear of the
 * game's emerald accent so chrome never reads as a target.
 */
final class VertexShapes
{
    /**
     * @var array<string, array{label: string, hex: string}>
     */
    private const SHAPES = [
        'disc' => ['label' => 'Disc', 'hex' => '#38BDF8'],
        'block' => ['label' => 'Block', 'hex' => '#F59E0B'],
        'bar' => ['label' => 'Bar', 'hex' => '#A855F7'],
        'ring' => ['label' => 'Ring', 'hex' => '#F43F5E'],
    ];

    /**
     * Every form, in the order difficulty levels widen through them.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::SHAPES);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::SHAPES);
    }

    /**
     * The name the target prompt announces ("Ring").
     */
    public static function label(string $key): string
    {
        return self::SHAPES[$key]['label'] ?? ucfirst($key);
    }

    public static function hex(string $key): string
    {
        return self::SHAPES[$key]['hex'] ?? '#94A3B8';
    }

    /**
     * Narrow the vocabulary to a validated pool. Two forms is the floor — a
     * one-form pool would make every round a target and kill the go/no-go.
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

        return count($pool) >= 2 ? $pool : ['disc', 'block', 'ring'];
    }
}
