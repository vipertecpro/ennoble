<?php

namespace Vipertecpro\Scene3d\Scene;

/** A named animation clip baked into a glTF model — a character's walk cycle. */
final class Clip
{
    public function __construct(
        public readonly string $name,
        public readonly bool $loop = true,
        public readonly float $speed = 1.0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'n' => $this->name,
            'l' => $this->loop ? 1 : 0,
            'sp' => $this->speed === 1.0 ? 0.0 : $this->speed,
        ], static fn (mixed $value): bool => $value !== 0 && $value !== 0.0 && $value !== '');
    }
}
