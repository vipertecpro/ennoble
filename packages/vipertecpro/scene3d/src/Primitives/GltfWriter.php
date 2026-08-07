<?php

namespace Vipertecpro\Scene3d\Primitives;

/**
 * Encodes a {@see Mesh} as a self-contained glTF 2.0 document.
 *
 * The buffer is embedded as a base64 data URI rather than written alongside as
 * a .bin, so a primitive is one file with no companion to lose in a build
 * pipeline. These meshes are a few kilobytes; the ~33% base64 overhead is
 * irrelevant next to shipping two files per shape.
 *
 * POSITION accessors carry min/max because the glTF spec REQUIRES them, and
 * loaders use them for bounding boxes and frustum culling. Omitting them
 * produces an asset that loads in forgiving viewers and then culls incorrectly
 * on device — the worst kind of bug to chase.
 */
final class GltfWriter
{
    private const FLOAT = 5126;

    private const UNSIGNED_SHORT = 5123;

    private const ARRAY_BUFFER = 34962;

    private const ELEMENT_ARRAY_BUFFER = 34963;

    /**
     * @return array<string, mixed>
     */
    public function encode(Mesh $mesh, string $name): array
    {
        $positionBytes = $this->packFloats($mesh->positions);
        $normalBytes = $this->packFloats($mesh->normals);
        $indexBytes = $this->packUnsignedShorts($mesh->indices);

        // Index data must start on a 4-byte boundary; unsigned shorts are only
        // 2-byte aligned, so pad when the preceding data lands us odd.
        $indexOffset = strlen($positionBytes) + strlen($normalBytes);
        $padding = (4 - ($indexOffset % 4)) % 4;
        $buffer = $positionBytes.$normalBytes.str_repeat("\0", $padding).$indexBytes;
        $indexOffset += $padding;

        [$min, $max] = $this->bounds($mesh->positions);

        return [
            'asset' => [
                'version' => '2.0',
                'generator' => 'vipertecpro/scene3d primitive generator',
            ],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0, 'name' => $name]],
            'meshes' => [[
                'name' => $name,
                'primitives' => [[
                    'attributes' => ['POSITION' => 0, 'NORMAL' => 1],
                    'indices' => 2,
                    'material' => 0,
                ]],
            ]],
            // A neutral white dielectric. The scene API tints per node, so the
            // asset must not bake in a colour of its own.
            'materials' => [[
                'name' => 'scene3d-default',
                'pbrMetallicRoughness' => [
                    'baseColorFactor' => [1.0, 1.0, 1.0, 1.0],
                    'metallicFactor' => 0.0,
                    'roughnessFactor' => 0.5,
                ],
                'doubleSided' => false,
            ]],
            'accessors' => [
                [
                    'bufferView' => 0,
                    'componentType' => self::FLOAT,
                    'count' => $mesh->vertexCount(),
                    'type' => 'VEC3',
                    'min' => $min,
                    'max' => $max,
                ],
                [
                    'bufferView' => 1,
                    'componentType' => self::FLOAT,
                    'count' => $mesh->vertexCount(),
                    'type' => 'VEC3',
                ],
                [
                    'bufferView' => 2,
                    'componentType' => self::UNSIGNED_SHORT,
                    'count' => count($mesh->indices),
                    'type' => 'SCALAR',
                ],
            ],
            'bufferViews' => [
                [
                    'buffer' => 0,
                    'byteOffset' => 0,
                    'byteLength' => strlen($positionBytes),
                    'target' => self::ARRAY_BUFFER,
                ],
                [
                    'buffer' => 0,
                    'byteOffset' => strlen($positionBytes),
                    'byteLength' => strlen($normalBytes),
                    'target' => self::ARRAY_BUFFER,
                ],
                [
                    'buffer' => 0,
                    'byteOffset' => $indexOffset,
                    'byteLength' => strlen($indexBytes),
                    'target' => self::ELEMENT_ARRAY_BUFFER,
                ],
            ],
            'buffers' => [[
                'byteLength' => strlen($buffer),
                'uri' => 'data:application/octet-stream;base64,'.base64_encode($buffer),
            ]],
        ];
    }

    public function toJson(Mesh $mesh, string $name): string
    {
        return json_encode($this->encode($mesh, $name), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<float>  $values
     */
    private function packFloats(array $values): string
    {
        // Little-endian float32: the byte order glTF mandates.
        return pack('g*', ...$values);
    }

    /**
     * @param  list<int>  $values
     */
    private function packUnsignedShorts(array $values): string
    {
        return pack('v*', ...$values);
    }

    /**
     * @param  list<float>  $positions
     * @return array{0: list<float>, 1: list<float>}
     */
    private function bounds(array $positions): array
    {
        $min = [INF, INF, INF];
        $max = [-INF, -INF, -INF];

        for ($i = 0; $i < count($positions); $i += 3) {
            for ($axis = 0; $axis < 3; $axis++) {
                $value = $positions[$i + $axis];
                $min[$axis] = min($min[$axis], $value);
                $max[$axis] = max($max[$axis], $value);
            }
        }

        return [
            array_map(static fn (float $v): float => round($v, 6), $min),
            array_map(static fn (float $v): float => round($v, 6), $max),
        ];
    }
}
