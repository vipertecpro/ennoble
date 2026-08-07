<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * A physically-based surface. Filament is a PBR renderer, so these are the
 * parameters that actually drive its shading — exposing anything else would
 * be inventing knobs the renderer cannot honour.
 *
 * Only applies to primitives. A glTF model brings its own authored materials,
 * and overriding them from PHP would fight the artist.
 */
final class Material
{
    public function __construct(
        public readonly string $color = '#FFFFFF',
        public readonly float $metallic = 0.0,
        public readonly float $roughness = 0.5,
        public readonly float $emissive = 0.0,
    ) {}

    public static function solid(string $color): self
    {
        return new self(color: $color);
    }

    /** A surface that glows — the cheap way to make something read as "live". */
    public static function glowing(string $color, float $strength = 1.0): self
    {
        return new self(color: $color, roughness: 0.35, emissive: max(0.0, $strength));
    }

    public static function metal(string $color, float $roughness = 0.25): self
    {
        return new self(color: $color, metallic: 1.0, roughness: $roughness);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'c' => $this->color,
            'me' => $this->metallic,
            'ro' => $this->roughness === 0.5 ? 0.0 : $this->roughness,
            'em' => $this->emissive,
        ], static fn (mixed $value): bool => $value !== 0.0 && $value !== '');
    }
}
