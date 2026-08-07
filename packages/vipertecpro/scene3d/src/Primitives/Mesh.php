<?php

namespace Vipertecpro\Scene3d\Primitives;

/**
 * Raw triangle geometry, before it becomes glTF.
 *
 * Positions and normals are flat float lists in vertex order; indices are
 * triangle triples. Normals are authored per-vertex rather than derived at
 * load time because Filament shades from the tangent frame — a mesh without
 * them renders flat black, which is the single most confusing failure a
 * newcomer to PBR can hit.
 */
final class Mesh
{
    /**
     * @param  list<float>  $positions
     * @param  list<float>  $normals
     * @param  list<int>  $indices
     */
    public function __construct(
        public readonly array $positions,
        public readonly array $normals,
        public readonly array $indices,
    ) {}

    public function vertexCount(): int
    {
        return intdiv(count($this->positions), 3);
    }

    public function triangleCount(): int
    {
        return intdiv(count($this->indices), 3);
    }
}
