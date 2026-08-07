<?php

namespace Vipertecpro\Scene3d\Scene;

/** A rotation that runs forever on the render thread. */
final class Spin
{
    public function __construct(
        public readonly string $axis = 'y',
        public readonly float $seconds = 4.0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['a' => $this->axis, 's' => $this->seconds];
    }
}
