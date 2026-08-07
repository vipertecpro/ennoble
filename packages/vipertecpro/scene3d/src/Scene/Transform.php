<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * A node's placement in the world: position, uniform scale, Euler rotation.
 *
 * Rotation is stored in DEGREES because that is what a person authoring a
 * scene in PHP thinks in; the renderer converts once at the boundary. Scale is
 * uniform on purpose — non-uniform scale breaks normals on lit geometry and is
 * a common source of "why is my model lit wrong", so v1 does not offer the
 * foot-gun.
 */
final class Transform
{
    public function __construct(
        public readonly float $x = 0.0,
        public readonly float $y = 0.0,
        public readonly float $z = 0.0,
        public readonly float $scale = 1.0,
        public readonly float $rotateX = 0.0,
        public readonly float $rotateY = 0.0,
        public readonly float $rotateZ = 0.0,
    ) {}

    public function at(float $x, float $y, float $z): self
    {
        return new self($x, $y, $z, $this->scale, $this->rotateX, $this->rotateY, $this->rotateZ);
    }

    public function scaled(float $scale): self
    {
        return new self($this->x, $this->y, $this->z, $scale, $this->rotateX, $this->rotateY, $this->rotateZ);
    }

    public function rotated(float $x = 0.0, float $y = 0.0, float $z = 0.0): self
    {
        return new self($this->x, $this->y, $this->z, $this->scale, $x, $y, $z);
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
        return array_filter([
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            's' => $this->scale === 1.0 ? 0.0 : $this->scale,
            'rx' => $this->rotateX,
            'ry' => $this->rotateY,
            'rz' => $this->rotateZ,
        ], static fn (float $value): bool => $value !== 0.0);
    }
}
