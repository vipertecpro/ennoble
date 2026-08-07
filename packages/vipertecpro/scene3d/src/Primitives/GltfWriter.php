<?php

namespace Vipertecpro\Scene3d\Primitives;

/**
 * Encodes a {@see Mesh} as a self-contained glTF 2.0 document.
 *
 * {@see toGlb()} is what ships — see the note there for why the JSON form's
 * data URI could not be used. This array/JSON form remains the single source
 * of truth for the document's structure, and is far easier to inspect and test
 * than a binary blob.
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
     * Encode as binary glTF (.glb) — the format actually shipped.
     *
     * WHY NOT THE .gltf ABOVE. A .gltf carries its buffer as a `data:` URI,
     * which the loader must resolve through its resource layer. Filament's
     * gltfio failed that resolution on device, logged one line, and then read
     * the unresolved buffer anyway — a null memcpy inside glBufferSubData, so
     * the process died in native code with a stack that named nothing of ours.
     * A .glb has no URI: the buffer is a chunk in the file, so there is nothing
     * to resolve and nothing to fail. It is also ~25% smaller, having dropped
     * base64.
     *
     * The JSON half is byte-identical to {@see encode()} except that its buffer
     * carries no `uri` — which is exactly what the spec requires for the GLB
     * buffer, and is what tells a reader to look in the BIN chunk.
     */
    public function toGlb(Mesh $mesh, string $name): string
    {
        $document = $this->encode($mesh, $name);

        $uri = $document['buffers'][0]['uri'];
        $binary = base64_decode(substr($uri, strpos($uri, ',') + 1), strict: true);

        unset($document['buffers'][0]['uri']);

        $json = json_encode($document, JSON_THROW_ON_ERROR);

        // Both chunks must be 4-byte aligned. The spec is specific about the
        // padding VALUE, not just the length: JSON pads with spaces so it stays
        // parseable, BIN pads with zeros.
        $json .= str_repeat(' ', (4 - (strlen($json) % 4)) % 4);
        $binary .= str_repeat("\0", (4 - (strlen($binary) % 4)) % 4);

        $chunks = pack('Va4', strlen($json), 'JSON').$json
            .pack('Va4', strlen($binary), "BIN\0").$binary;

        // 12-byte header: magic, version, total length including the header.
        return pack('a4VV', 'glTF', 2, 12 + strlen($chunks)).$chunks;
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
