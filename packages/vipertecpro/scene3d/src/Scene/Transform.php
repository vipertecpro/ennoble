<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * A node's placement in the world: position, scale, Euler rotation.
 *
 * Rotation is stored in DEGREES because that is what a person authoring a
 * scene in PHP thinks in; the renderer converts once at the boundary.
 *
 * Scale is per-axis, but uniform scale stays the DEFAULT and the compact wire
 * case. Non-uniform scale requires the renderer to shade with the
 * inverse-transpose of the basis or lighting goes wrong on the stretched axes;
 * Filament does this correctly for axis-aligned scaling, which is what shaping
 * a box into a floor or a wall needs. It is still the wrong tool for stretching
 * a detailed lit model — reach for it to shape primitives, not characters.
 */
final class Transform
{
    public function __construct(
        public readonly float $x = 0.0,
        public readonly float $y = 0.0,
        public readonly float $z = 0.0,
        public readonly float $scale = 1.0,
        public readonly ?float $scaleY = null,
        public readonly ?float $scaleZ = null,
        public readonly float $rotateX = 0.0,
        public readonly float $rotateY = 0.0,
        public readonly float $rotateZ = 0.0,
    ) {}

    public function at(float $x, float $y, float $z): self
    {
        return new self($x, $y, $z, $this->scale, $this->scaleY, $this->scaleZ, $this->rotateX, $this->rotateY, $this->rotateZ);
    }

    public function scaled(float $scale): self
    {
        return new self($this->x, $this->y, $this->z, $scale, null, null, $this->rotateX, $this->rotateY, $this->rotateZ);
    }

    /** Shape a primitive per-axis — a box into a floor, a wall, a bar. */
    public function sized(float $x, float $y, float $z): self
    {
        return new self($this->x, $this->y, $this->z, $x, $y, $z, $this->rotateX, $this->rotateY, $this->rotateZ);
    }

    public function rotated(float $x = 0.0, float $y = 0.0, float $z = 0.0): self
    {
        return new self($this->x, $this->y, $this->z, $this->scale, $this->scaleY, $this->scaleZ, $x, $y, $z);
    }

    private function isUniform(): bool
    {
        return $this->scaleY === null && $this->scaleZ === null;
    }

    /**
     * Compact wire form. Defaults are OMITTED rather than sent as zeroes: a
     * scene of a hundred nodes ships on every render, so the bytes that are
     * not there are the cheapest optimisation available.
     *
     * @return array<string, float>
     */
    public function toArray(): array
    {
        $wire = array_filter([
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            // Uniform scale keeps the single-key form it has always had; only
            // a genuinely non-uniform node pays for the extra two.
            's' => $this->scale === 1.0 ? 0.0 : $this->scale,
            'rx' => $this->rotateX,
            'ry' => $this->rotateY,
            'rz' => $this->rotateZ,
        ], static fn (float $value): bool => $value !== 0.0);

        if (! $this->isUniform()) {
            // Sent explicitly, including 1.0: with per-axis scale there is no
            // "absent means the same as the others" shorthand left to lean on.
            $wire['s'] = $this->scale;
            $wire['sy'] = $this->scaleY ?? $this->scale;
            $wire['sz'] = $this->scaleZ ?? $this->scale;
        }

        return $wire;
    }
}
