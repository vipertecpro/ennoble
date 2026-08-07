<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * A light. Also viewport furniture, for the same reason as the camera.
 *
 * Intensity is in Filament's physical units (lux for directional, candela for
 * point), which is why the defaults look like large numbers — a PBR renderer
 * expects real-world values, and inventing a friendlier 0-1 scale here would
 * just mislead anyone who later reads Filament's own docs.
 */
final class Light
{
    public const DIRECTIONAL = 'directional';

    public const POINT = 'point';

    public const AMBIENT = 'ambient';

    public function __construct(
        public readonly string $type = self::DIRECTIONAL,
        public readonly float $intensity = 80000.0,
        public readonly string $color = '#FFFFFF',
        public readonly float $x = 0.6,
        public readonly float $y = -1.0,
        public readonly float $z = -0.8,
    ) {}

    public static function key(float $intensity = 80000.0): self
    {
        return new self(self::DIRECTIONAL, $intensity);
    }

    public static function ambient(float $intensity = 30000.0, string $color = '#FFFFFF'): self
    {
        return new self(self::AMBIENT, $intensity, $color);
    }

    public static function point(float $x, float $y, float $z, float $intensity = 120000.0, string $color = '#FFFFFF'): self
    {
        return new self(self::POINT, $intensity, $color, $x, $y, $z);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            't' => $this->type,
            'i' => $this->intensity,
            'c' => $this->color,
            'x' => $this->x, 'y' => $this->y, 'z' => $this->z,
        ];
    }
}
