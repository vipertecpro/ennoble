<?php

namespace App\Domain\Games\Vertex;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline wave generator for Barrage.
 *
 * Each wave is a formation of invaders plus the criterion that says which of
 * them may be fired on. The generator's real job is guaranteeing every wave is
 * a genuine SEARCH rather than a glance:
 *
 *  - Every wave contains at least one target and at least one decoy. A wave of
 *    all-targets is a tapping drill; a wave of none is dead air.
 *  - Targets are never the majority. The whole difficulty of visual search is
 *    that hits are sparse among distractors, and a formation that is mostly
 *    targets collapses back into "tap everything".
 *  - Decoys are seeded to share an attribute with the criterion wherever the
 *    pools allow, so under "fire at blue rings" the field still holds blue
 *    non-rings and non-blue rings. Without those the conjunction never has to
 *    be held — a single attribute would separate the field on its own.
 *
 * Everything is drawn from a stable seed, so a session replays the same run.
 */
final class VertexGenerator
{
    /** Criterion forms in ascending difficulty; levels pick a subset. */
    private const FORMS = ['shape', 'colour', 'not_shape', 'not_colour', 'both'];

    /** Targets may never exceed this share of a formation. */
    private const MAX_TARGET_SHARE = 0.5;

    /**
     * Build the session's waves.
     *
     * @return list<array{
     *     criterion: array{type: string, shape?: string, colour?: string},
     *     order: string,
     *     invaders: list<array{id: int, shape: string, colour: string, is_target: bool}>
     * }>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];

        $shapes = VertexVocabulary::pool(
            is_array($config['shapes'] ?? null) ? $config['shapes'] : [],
            VertexVocabulary::hasShape(...),
            ['disc', 'block', 'ring'],
        );
        $colours = VertexVocabulary::pool(
            is_array($config['colours'] ?? null) ? $config['colours'] : [],
            VertexVocabulary::hasColour(...),
            ['blue', 'amber', 'rose'],
        );
        $forms = $this->forms($config);
        $size = max(4, min((int) ($config['formation'] ?? 9), 16));

        $waves = [];

        for ($index = 0; $index < $count; $index++) {
            $waves[] = $this->wave(
                seed: $seed.':wave:'.$index,
                shapes: $shapes,
                colours: $colours,
                forms: $forms,
                size: $size,
            );
        }

        return $waves;
    }

    /**
     * @param  list<string>  $shapes
     * @param  list<string>  $colours
     * @param  list<string>  $forms
     * @return array{criterion: array{type: string, shape?: string, colour?: string}, order: string, invaders: list<array{id: int, shape: string, colour: string, is_target: bool}>}
     */
    private function wave(string $seed, array $shapes, array $colours, array $forms, int $size): array
    {
        $criterion = $this->criterion($seed, $shapes, $colours, $forms);

        // Build the field, then repair it: it is far simpler to guarantee the
        // target/decoy balance by fixing up a drawn field than by trying to
        // draw a correct one in one pass.
        $invaders = [];

        for ($slot = 0; $slot < $size; $slot++) {
            $invaders[] = [
                'id' => $slot,
                'shape' => $shapes[$this->intFromSeed($seed.':shape:'.$slot, 0, count($shapes) - 1)],
                'colour' => $colours[$this->intFromSeed($seed.':colour:'.$slot, 0, count($colours) - 1)],
            ];
        }

        $invaders = $this->balance($invaders, $criterion, $seed, $shapes, $colours, $size);

        return [
            'criterion' => $criterion,
            'order' => VertexVocabulary::orderLabel($criterion),
            'invaders' => array_map(
                static fn (array $invader): array => [
                    ...$invader,
                    'is_target' => VertexVocabulary::matches($criterion, $invader),
                ],
                $invaders,
            ),
        ];
    }

    /**
     * Force at least one target, at least one decoy, and targets into the
     * minority — by overwriting individual slots with a known-good invader.
     *
     * @param  list<array{id: int, shape: string, colour: string}>  $invaders
     * @param  array{type: string, shape?: string, colour?: string}  $criterion
     * @param  list<string>  $shapes
     * @param  list<string>  $colours
     * @return list<array{id: int, shape: string, colour: string}>
     */
    private function balance(array $invaders, array $criterion, string $seed, array $shapes, array $colours, int $size): array
    {
        $maxTargets = max(1, (int) floor($size * self::MAX_TARGET_SHARE));

        $targetIndexes = static fn (array $field): array => array_keys(array_filter(
            $field,
            static fn (array $invader): bool => VertexVocabulary::matches($criterion, $invader),
        ));

        // Too many targets: turn the surplus into decoys.
        $targets = $targetIndexes($invaders);
        $surplus = count($targets) - $maxTargets;

        for ($step = 0; $step < $surplus; $step++) {
            $at = $targets[count($targets) - 1 - $step];
            $invaders[$at] = $this->decoy($criterion, $seed.':decoy:'.$at, $shapes, $colours, $invaders[$at]);
        }

        // No targets at all: promote one slot.
        if ($targetIndexes($invaders) === []) {
            $at = $this->intFromSeed($seed.':promote', 0, $size - 1);
            $invaders[$at] = $this->target($criterion, $seed.':target:'.$at, $shapes, $colours, $invaders[$at]);
        }

        // No decoys at all: demote one slot that is not the sole target.
        $targets = $targetIndexes($invaders);

        if (count($targets) === $size) {
            $at = $targets[count($targets) - 1];
            $invaders[$at] = $this->decoy($criterion, $seed.':demote:'.$at, $shapes, $colours, $invaders[$at]);
        }

        return array_values($invaders);
    }

    /**
     * @param  array{type: string, shape?: string, colour?: string}  $criterion
     * @param  list<string>  $shapes
     * @param  list<string>  $colours
     * @param  array{id: int, shape: string, colour: string}  $invader
     * @return array{id: int, shape: string, colour: string}
     */
    private function target(array $criterion, string $seed, array $shapes, array $colours, array $invader): array
    {
        return match ($criterion['type']) {
            'shape' => [...$invader, 'shape' => $criterion['shape']],
            'colour' => [...$invader, 'colour' => $criterion['colour']],
            'both' => [...$invader, 'shape' => $criterion['shape'], 'colour' => $criterion['colour']],
            'not_shape' => [...$invader, 'shape' => $this->other($shapes, $criterion['shape'], $seed)],
            'not_colour' => [...$invader, 'colour' => $this->other($colours, $criterion['colour'], $seed)],
            default => $invader,
        };
    }

    /**
     * A decoy that still shares an attribute with the criterion where it can,
     * so a conjunction wave cannot be solved by looking at one attribute.
     *
     * @param  array{type: string, shape?: string, colour?: string}  $criterion
     * @param  list<string>  $shapes
     * @param  list<string>  $colours
     * @param  array{id: int, shape: string, colour: string}  $invader
     * @return array{id: int, shape: string, colour: string}
     */
    private function decoy(array $criterion, string $seed, array $shapes, array $colours, array $invader): array
    {
        return match ($criterion['type']) {
            'shape' => [...$invader, 'shape' => $this->other($shapes, $criterion['shape'], $seed)],
            'colour' => [...$invader, 'colour' => $this->other($colours, $criterion['colour'], $seed)],
            'not_shape' => [...$invader, 'shape' => $criterion['shape']],
            'not_colour' => [...$invader, 'colour' => $criterion['colour']],
            // Break exactly ONE half of the conjunction, alternating which, so
            // the field fills with near-misses on both attributes.
            'both' => $this->intFromSeed($seed, 0, 1) === 0
                ? [...$invader, 'shape' => $this->other($shapes, $criterion['shape'], $seed), 'colour' => $criterion['colour']]
                : [...$invader, 'shape' => $criterion['shape'], 'colour' => $this->other($colours, $criterion['colour'], $seed)],
            default => $invader,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function forms(array $config): array
    {
        $configured = is_array($config['forms'] ?? null) ? $config['forms'] : [];
        $forms = array_values(array_unique(array_filter(
            $configured,
            static fn (mixed $form): bool => in_array($form, self::FORMS, true),
        )));

        return $forms !== [] ? $forms : ['shape'];
    }

    /**
     * @param  list<string>  $shapes
     * @param  list<string>  $colours
     * @param  list<string>  $forms
     * @return array{type: string, shape?: string, colour?: string}
     */
    private function criterion(string $seed, array $shapes, array $colours, array $forms): array
    {
        $type = $forms[$this->intFromSeed($seed.':form', 0, count($forms) - 1)];
        $shape = $shapes[$this->intFromSeed($seed.':crit-shape', 0, count($shapes) - 1)];
        $colour = $colours[$this->intFromSeed($seed.':crit-colour', 0, count($colours) - 1)];

        return match ($type) {
            'colour', 'not_colour' => ['type' => $type, 'colour' => $colour],
            'both' => ['type' => $type, 'shape' => $shape, 'colour' => $colour],
            default => ['type' => $type, 'shape' => $shape],
        };
    }

    /**
     * @param  list<string>  $pool
     */
    private function other(array $pool, string $exclude, string $seed): string
    {
        $candidates = array_values(array_filter($pool, static fn (string $value): bool => $value !== $exclude));

        if ($candidates === []) {
            return $exclude;
        }

        return $candidates[$this->intFromSeed($seed.':other', 0, count($candidates) - 1)];
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return $min + (int) (hexdec(substr(hash('sha1', $seed), 0, 12)) % ($max - $min + 1));
    }
}
