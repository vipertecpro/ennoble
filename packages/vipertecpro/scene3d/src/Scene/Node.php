<?php

namespace Vipertecpro\Scene3d\Scene;

use InvalidArgumentException;

/**
 * One thing in the scene: either a primitive shape or a loaded glTF model.
 *
 * IDENTITY IS THE CONTRACT. The renderer matches nodes across frames by `id`
 * and updates them in place, so a node keeps its GPU resources, its skeleton
 * and its running animations for as long as its id survives. Reusing an id for
 * a different object, or regenerating ids each frame, forces a rebuild and
 * throws all of that away — which is exactly how a 3D scene ends up stuttering.
 *
 * `r` on the wire is how the native side avoids deep-comparing every node: it
 * skips any node whose `r` is unchanged. It is therefore a CONTENT HASH of
 * everything else in the payload, and nothing else will do.
 *
 * It used to be a mutation counter, and that was a serious bug. A counter
 * records how many builder calls were made, not what they set — so a node
 * rebuilt each frame the same WAY but with a different position and colour
 * carried an identical counter, and the renderer skipped the update. Pieces
 * never moved, characters never jumped, and none of it was visible from PHP,
 * because the wire looked correct apart from that one number.
 */
final class Node
{
    private function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly ?string $shape,
        public readonly ?string $model,
        public readonly Transform $transform,
        public readonly ?Material $material,
        public readonly float $opacity,
        public readonly ?Spin $spin,
        public readonly ?Move $move,
        public readonly ?Clip $clip,
        public readonly bool $tappable,
        public readonly int $revision,
    ) {}

    /**
     * A built-in primitive — cheap, needs no asset, ideal for prototyping and
     * for the geometric games this plugin was written for.
     */
    public static function shape(string $id, string $shape): self
    {
        if (! in_array($shape, Shapes::ALL, true)) {
            throw new InvalidArgumentException(
                "Unknown shape [{$shape}]. Expected one of: ".implode(', ', Shapes::ALL)
            );
        }

        return new self($id, 'shape', $shape, null, new Transform, null, 1.0, null, null, null, false, 1);
    }

    /**
     * A glTF/GLB model, resolved against the app's bundled 3D assets.
     *
     * glTF is the only accepted format because it is the one both platforms
     * load natively through gltfio, including skinning and animation — a
     * character authored once works on iOS and Android with no conversion.
     */
    public static function model(string $id, string $path): self
    {
        if (! preg_match('/\.(gltf|glb)$/i', $path)) {
            throw new InvalidArgumentException(
                "Model [{$path}] must be .gltf or .glb — the only formats both renderers load natively."
            );
        }

        return new self($id, 'model', null, $path, new Transform, null, 1.0, null, null, null, false, 1);
    }

    public function at(float $x, float $y, float $z): self
    {
        return $this->with(transform: $this->transform->at($x, $y, $z));
    }

    public function scale(float $scale): self
    {
        return $this->with(transform: $this->transform->scaled($scale));
    }

    /** Shape a primitive per-axis. See {@see Transform} for when not to. */
    public function size(float $x, float $y, float $z): self
    {
        return $this->with(transform: $this->transform->sized($x, $y, $z));
    }

    public function rotate(float $x = 0.0, float $y = 0.0, float $z = 0.0): self
    {
        return $this->with(transform: $this->transform->rotated($x, $y, $z));
    }

    public function color(string $hex): self
    {
        return $this->with(material: Material::solid($hex));
    }

    public function material(Material $material): self
    {
        return $this->with(material: $material);
    }

    public function opacity(float $opacity): self
    {
        return $this->with(opacity: max(0.0, min(1.0, $opacity)));
    }

    /** Rotate forever. Runs on the render thread; PHP is not involved again. */
    public function spin(string $axis = 'y', float $seconds = 4.0): self
    {
        return $this->with(spin: new Spin($axis, $seconds));
    }

    /** Travel to a point over a duration, once. */
    public function moveTo(float $x, float $y, float $z, float $seconds): self
    {
        return $this->with(move: new Move($x, $y, $z, $seconds));
    }

    /** Play a named animation clip from the model's glTF. */
    public function play(string $clip, bool $loop = true, float $speed = 1.0): self
    {
        return $this->with(clip: new Clip($clip, $loop, $speed));
    }

    /** Report taps on this node back to PHP by id. */
    public function tappable(bool $tappable = true): self
    {
        return $this->with(tappable: $tappable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'id' => $this->id,
        ];

        if ($this->kind === 'model') {
            $payload['m'] = $this->model;
        } else {
            $payload['g'] = $this->shape;
        }

        $payload += $this->transform->toArray();

        if ($this->material !== null) {
            $payload['mat'] = $this->material->toArray();
        }

        if ($this->opacity !== 1.0) {
            $payload['o'] = $this->opacity;
        }

        if ($this->spin !== null) {
            $payload['spin'] = $this->spin->toArray();
        }

        if ($this->move !== null) {
            $payload['move'] = $this->move->toArray();
        }

        if ($this->clip !== null) {
            $payload['clip'] = $this->clip->toArray();
        }

        if ($this->tappable) {
            $payload['tap'] = 1;
        }

        // Derived from everything above, so it changes exactly when the node
        // does. Masked to a positive 32-bit int because the renderer reads it
        // with optInt and a full crc32 overflows a signed Int.
        return ['id' => $this->id, 'r' => $this->contentRevision($payload), ...$payload];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function contentRevision(array $payload): int
    {
        return crc32(json_encode($payload, JSON_THROW_ON_ERROR)) & 0x7FFFFFFF;
    }

    /**
     * Every mutation bumps the revision — that counter is what lets the
     * renderer skip untouched nodes without comparing their contents.
     */
    private function with(
        ?Transform $transform = null,
        ?Material $material = null,
        ?float $opacity = null,
        ?Spin $spin = null,
        ?Move $move = null,
        ?Clip $clip = null,
        ?bool $tappable = null,
    ): self {
        return new self(
            id: $this->id,
            kind: $this->kind,
            shape: $this->shape,
            model: $this->model,
            transform: $transform ?? $this->transform,
            material: $material ?? $this->material,
            opacity: $opacity ?? $this->opacity,
            spin: $spin ?? $this->spin,
            move: $move ?? $this->move,
            clip: $clip ?? $this->clip,
            tappable: $tappable ?? $this->tappable,
            revision: $this->revision + 1,
        );
    }
}
