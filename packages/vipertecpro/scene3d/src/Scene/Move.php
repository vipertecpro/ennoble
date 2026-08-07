<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * A one-shot translation to a point.
 *
 * This is the 3D twin of EDGE's transform tweens, and exists for the same
 * reason: PHP's poll floor is ~250ms, so movement must be DECLARED and left to
 * the renderer rather than stepped frame by frame.
 */
final class Move
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly float $seconds,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z, 's' => $this->seconds];
    }
}
