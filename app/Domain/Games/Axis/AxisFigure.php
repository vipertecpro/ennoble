<?php

namespace App\Domain\Games\Axis;

/**
 * A figure made of unit cubes on a lattice — the Shepard–Metzler solid.
 *
 * Immutable, and compared by SHAPE rather than by coordinates: two figures are
 * equal when some rotation carries one onto the other. Position and orientation
 * are presentation; only the arrangement is identity.
 *
 * Integer coordinates throughout, deliberately. Rotations of a lattice figure
 * are exact in integers, so congruence is an exact string comparison — with
 * floats it would need a tolerance, and a tolerance is how a round ends up with
 * two right answers.
 */
final class AxisFigure
{
    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $cells
     */
    public function __construct(public readonly array $cells) {}

    /**
     * @param  array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}, 2: array{0: int, 1: int, 2: int}}  $matrix
     */
    public function rotated(array $matrix): self
    {
        return new self(array_map(
            static fn (array $cell): array => [
                $matrix[0][0] * $cell[0] + $matrix[0][1] * $cell[1] + $matrix[0][2] * $cell[2],
                $matrix[1][0] * $cell[0] + $matrix[1][1] * $cell[1] + $matrix[1][2] * $cell[2],
                $matrix[2][0] * $cell[0] + $matrix[2][1] * $cell[1] + $matrix[2][2] * $cell[2],
            ],
            $this->cells,
        ));
    }

    /** The reflection through x. Never reachable by any rotation, for a chiral figure. */
    public function mirrored(): self
    {
        return new self(array_map(
            static fn (array $cell): array => [-$cell[0], $cell[1], $cell[2]],
            $this->cells,
        ));
    }

    /**
     * A canonical form: translated to the origin and sorted, so two figures in
     * the same orientation produce the same string wherever they sit in space.
     */
    public function key(): string
    {
        $minX = min(array_column($this->cells, 0));
        $minY = min(array_column($this->cells, 1));
        $minZ = min(array_column($this->cells, 2));

        $normalised = array_map(
            static fn (array $c): string => ($c[0] - $minX).','.($c[1] - $minY).','.($c[2] - $minZ),
            $this->cells,
        );

        sort($normalised);

        return implode('|', $normalised);
    }

    /** True when some rotation carries this figure onto the other. */
    public function isSameShapeAs(self $other): bool
    {
        $target = $other->key();

        foreach (AxisRotations::all() as $matrix) {
            if ($this->rotated($matrix)->key() === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the figure differs from its own mirror image — the property
     * that makes a round answerable at all.
     *
     * An achiral figure (an L, a straight bar, anything with a mirror plane) is
     * congruent to its reflection, so "which of these is the rotation and which
     * is the mirror" has TWO correct answers and the player cannot be wrong.
     * Those figures have to be discarded at generation, not corrected later.
     */
    public function isChiral(): bool
    {
        return ! $this->isSameShapeAs($this->mirrored());
    }

    /** Cells re-centred on the figure's own bounding box, for placing it in a scene. */
    public function centredCells(): array
    {
        $centreX = (min(array_column($this->cells, 0)) + max(array_column($this->cells, 0))) / 2;
        $centreY = (min(array_column($this->cells, 1)) + max(array_column($this->cells, 1))) / 2;
        $centreZ = (min(array_column($this->cells, 2)) + max(array_column($this->cells, 2))) / 2;

        return array_map(
            static fn (array $c): array => [$c[0] - $centreX, $c[1] - $centreY, $c[2] - $centreZ],
            $this->cells,
        );
    }
}
